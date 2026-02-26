import json
import os
import base64
from datetime import datetime
from http.server import HTTPServer, BaseHTTPRequestHandler
import hashlib

class CompleteFaceHandler(BaseHTTPRequestHandler):
    
    def _set_headers(self, status=200):
        self.send_response(status)
        self.send_header('Content-type', 'application/json')
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS')
        self.send_header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
    
    def do_OPTIONS(self):
        self._set_headers()
        self.end_headers()
    
    def do_GET(self):
        if self.path == '/test':
            self._set_headers()
            self.end_headers()
            response = {'success': True, 'message': '✅ Servicio funcionando!'}
            self.wfile.write(json.dumps(response).encode('utf-8'))
        elif self.path == '/face_stats':
            self.handle_face_stats()
        else:
            self.send_error(404)
    
    def do_POST(self):
        if self.path == '/register_face':
            self.handle_register_face()
        elif self.path == '/verify_face':
            self.handle_verify_face()
        else:
            self.send_error(404)
    
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
            
            # Guardar imagen
            timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
            img_filename = f"face_data/user_{user_id}_{timestamp}.jpg"
            
            if image_data and ',' in image_data:
                image_data = image_data.split(',')[1]
            
            if image_data:
                with open(img_filename, 'wb') as f:
                    f.write(base64.b64decode(image_data))
                print(f"💾 Imagen guardada: {img_filename}")
                
                # Crear archivo de metadata
                meta_filename = f"face_data/user_{user_id}_meta.json"
                user_data = {
                    'user_id': user_id,
                    'registered_at': datetime.now().isoformat(),
                    'image_file': img_filename,
                    'status': 'active'
                }
                with open(meta_filename, 'w') as f:
                    json.dump(user_data, f, indent=2)
                
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
            
            # Simular verificación (en producción usarías Face Recognition)
            # Por ahora, asumimos que el usuario 2 es el único registrado
            user_id = self.simulate_face_recognition(image_data)
            
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
                    'message': '❌ Rostro no reconocido'
                }
                print("❌ Verificación FALLIDA - Rostro no reconocido")
            
            self.wfile.write(json.dumps(response).encode('utf-8'))
            
        except Exception as e:
            print(f"💥 Error en verificación: {e}")
            self._set_headers(500)
            self.end_headers()
            response = {'success': False, 'message': f'Error: {str(e)}'}
            self.wfile.write(json.dumps(response).encode('utf-8'))
    
    def simulate_face_recognition(self, image_data):
        """Simula el reconocimiento facial - siempre retorna usuario 2 para demo"""
        # En producción aquí iría el código real de Face Recognition
        # Por ahora, asumimos que el usuario 2 está registrado
        
        # Verificar si hay archivos de usuario 2
        user_files = [f for f in os.listdir('face_data') if f.startswith('user_2_') and f.endswith('.jpg')]
        
        if user_files:
            print(f"✅ Usuario 2 encontrado con {len(user_files)} registros")
            return 2  # Retorna el ID del usuario reconocido
        else:
            print("❌ No hay usuarios registrados")
            return None
    
    def handle_face_stats(self):
        try:
            # Contar registros faciales
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

def run_server():
    PORT = 5000
    
    print("=" * 60)
    print("🚀 SERVICIO FACIAL COMPLETO - REGISTRO + VERIFICACIÓN")
    print("=" * 60)
    print(f"📍 URL: http://localhost:{PORT}")
    print("🔍 Endpoints:")
    print("   GET  /test        - Prueba de conexión")
    print("   GET  /face_stats  - Estadísticas")
    print("   POST /register_face - Registrar rostro")
    print("   POST /verify_face - Verificar rostro")
    print("=" * 60)
    
    try:
        server = HTTPServer(('localhost', PORT), CompleteFaceHandler)
        print(f"✅ Servicio iniciado en puerto {PORT}")
        server.serve_forever()
    except Exception as e:
        print(f"💥 Error: {e}")

if __name__ == "__main__":
    run_server()
    

def update_database_after_registration(user_id):
    """Actualiza la base de datos después del registro facial"""
    try:
        conn = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='ferreteria_llano_acero_2'
        )
        
        cursor = conn.cursor()
        cursor.execute("""
            UPDATE usuarios 
            SET is_face_registered = 1, 
                last_verification = NOW(),
                verification_attempts = 0
            WHERE id_usuario = %s
        """, (user_id,))
        
        conn.commit()
        cursor.close()
        conn.close()
        print(f"✅ Base de datos actualizada para usuario {user_id}")
        return True
        
    except Exception as e:
        print(f"❌ Error actualizando BD: {e}")
        return False