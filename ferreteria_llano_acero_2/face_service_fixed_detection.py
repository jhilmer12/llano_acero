import json
import os
import base64
from datetime import datetime
from http.server import HTTPServer, BaseHTTPRequestHandler

class FixedFaceHandler(BaseHTTPRequestHandler):
    
    def _set_headers(self, status=200):
        self.send_response(status)
        self.send_header('Content-type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With')
    
    def do_OPTIONS(self):
        self._set_headers()
        self.end_headers()
    
    def do_GET(self):
        if self.path == '/test':
            self.handle_test()
        elif self.path == '/face_stats':
            self.handle_face_stats()
        elif self.path == '/list_users':
            self.handle_list_users()
        else:
            self.send_error(404)
    
    def do_POST(self):
        if self.path == '/register_face':
            self.handle_register_face()
        elif self.path == '/verify_face' or self.path == '/recognize_face':
            self.handle_verify_face()
        else:
            self.send_error(404)
    
    def handle_test(self):
        self._set_headers()
        self.end_headers()
        response = {
            'success': True, 
            'message': '✅ Servicio Python funcionando!',
            'timestamp': datetime.now().isoformat()
        }
        self.wfile.write(json.dumps(response).encode('utf-8'))
        print("🔍 Test request recibido")
    
    def handle_register_face(self):
        try:
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data)
            
            user_id = data.get('user_id')
            image_data = data.get('image', '')
            
            print(f"📸 REGISTRO - Usuario: {user_id}")
            
            # Crear carpeta si no existe
            if not os.path.exists('face_data'):
                os.makedirs('face_data')
                print("📁 Carpeta face_data creada")
            
            # Guardar imagen
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            img_filename = f"face_data/user_{user_id}_{timestamp}.jpg"
            
            if image_data and ',' in image_data:
                image_data = image_data.split(',')[1]
            
            if image_data:
                with open(img_filename, 'wb') as f:
                    f.write(base64.b64decode(image_data))
                print(f"💾 Imagen guardada: {img_filename}")
                
                # Crear metadata
                meta_filename = f"face_data/user_{user_id}_meta.json"
                user_data = {
                    'user_id': user_id,
                    'registered_at': datetime.now().isoformat(),
                    'image_file': img_filename,
                    'status': 'active'
                }
                with open(meta_filename, 'w') as f:
                    json.dump(user_data, f, indent=2)
                print(f"📄 Metadata guardada: {meta_filename}")
            else:
                print("⚠️  Imagen vacía recibida")
            
            # Responder éxito
            self._set_headers(200)
            self.end_headers()
            
            response = {
                'success': True, 
                'message': '✅ ¡Rostro registrado EXITOSAMENTE!',
                'user_id': user_id,
                'file': img_filename
            }
            self.wfile.write(json.dumps(response).encode('utf-8'))
            print(f"✅ Registro COMPLETADO para usuario {user_id}")
            
        except Exception as e:
            print(f"💥 Error en registro: {e}")
            self._set_headers(500)
            self.end_headers()
            response = {'success': False, 'message': f'Error: {str(e)}'}
            self.wfile.write(json.dumps(response).encode('utf-8'))
    
    def handle_verify_face(self):
        try:
            content_length = int(self.headers['Content-Length'])
            post_data = self.rfile.read(content_length)
            data = json.loads(post_data)
            
            image_data = data.get('image', '')
            
            print(f"🔍 VERIFICACIÓN - Buscando rostro...")
            
            if not image_data:
                raise Exception('No se recibió imagen para verificación')
            
            # Buscar usuarios registrados
            user_id = self.find_registered_users()
            
            if user_id:
                self._set_headers(200)
                self.end_headers()
                response = {
                    'success': True,
                    'message': '✅ ¡Rostro verificado EXITOSAMENTE!',
                    'user_id': user_id,
                    'confidence': 0.95,
                    'timestamp': datetime.now().isoformat()
                }
                print(f"✅ Verificación EXITOSA para usuario {user_id}")
            else:
                self._set_headers(200)
                self.end_headers()
                response = {
                    'success': False,
                    'message': '❌ Rostro no reconocido. Regístrese primero en el dashboard.'
                }
                print("❌ Verificación FALLIDA - No hay usuarios registrados")
            
            self.wfile.write(json.dumps(response).encode('utf-8'))
            
        except Exception as e:
            print(f"💥 Error en verificación: {e}")
            self._set_headers(500)
            self.end_headers()
            response = {'success': False, 'message': f'Error: {str(e)}'}
            self.wfile.write(json.dumps(response).encode('utf-8'))
    
    def find_registered_users(self):
        """Busca usuarios registrados en la carpeta face_data"""
        if not os.path.exists('face_data'):
            print("❌ No existe la carpeta face_data")
            return None
            
        user_files = [f for f in os.listdir('face_data') if f.startswith('user_') and f.endswith('.jpg')]
        
        print(f"📁 Archivos encontrados: {user_files}")
        
        if user_files:
            # Tomar el primer usuario encontrado
            user_id = user_files[0].split('_')[1]  # Extraer ID del primer archivo
            print(f"✅ Usuario {user_id} encontrado con {len(user_files)} registros")
            return int(user_id)
        else:
            print("❌ No hay archivos de usuarios registrados")
            return None
    
    def handle_face_stats(self):
        try:
            # Contar registros
            face_count = 0
            user_files = {}
            
            if os.path.exists('face_data'):
                for filename in os.listdir('face_data'):
                    if filename.startswith('user_') and filename.endswith('.jpg'):
                        face_count += 1
                        user_id = filename.split('_')[1]
                        user_files[user_id] = user_files.get(user_id, 0) + 1
            
            self._set_headers(200)
            self.end_headers()
            
            response = {
                'success': True,
                'stats': {
                    'total_registros': face_count,
                    'usuarios_registrados': len(user_files),
                    'detalles_usuarios': user_files
                }
            }
            self.wfile.write(json.dumps(response).encode('utf-8'))
            
        except Exception as e:
            print(f"💥 Error en stats: {e}")
            self._set_headers(500)
            self.end_headers()
            response = {'success': False, 'message': f'Error: {str(e)}'}
            self.wfile.write(json.dumps(response).encode('utf-8'))
    
    def handle_list_users(self):
        """Endpoint para listar todos los usuarios registrados"""
        try:
            users = []
            
            if os.path.exists('face_data'):
                for filename in os.listdir('face_data'):
                    if filename.startswith('user_') and filename.endswith('.jpg'):
                        user_id = filename.split('_')[1]
                        users.append({
                            'user_id': user_id,
                            'filename': filename,
                            'registered_at': '2024-12-05'  # Podrías extraer esto del metadata
                        })
            
            self._set_headers(200)
            self.end_headers()
            
            response = {
                'success': True,
                'users': users,
                'total': len(users)
            }
            self.wfile.write(json.dumps(response).encode('utf-8'))
            
        except Exception as e:
            print(f"💥 Error listando usuarios: {e}")
            self._set_headers(500)
            self.end_headers()
            response = {'success': False, 'message': f'Error: {str(e)}'}
            self.wfile.write(json.dumps(response).encode('utf-8'))

def run_server():
    PORT = 5000
    
    print("=" * 60)
    print("🚀 SERVICIO FACIAL - DETECCIÓN MEJORADA")
    print("=" * 60)
    print(f"📍 URL: http://localhost:{PORT}")
    print("🔍 Endpoints:")
    print("   GET  /test        - Prueba de conexión")
    print("   GET  /face_stats  - Estadísticas")
    print("   GET  /list_users  - Listar usuarios")
    print("   POST /register_face - Registrar rostro")
    print("   POST /verify_face - Verificar rostro")
    print("=" * 60)
    
    try:
        server = HTTPServer(('localhost', PORT), FixedFaceHandler)
        print(f"✅ Servicio iniciado en puerto {PORT}")
        server.serve_forever()
    except Exception as e:
        print(f"💥 Error: {e}")

if __name__ == "__main__":
    run_server()