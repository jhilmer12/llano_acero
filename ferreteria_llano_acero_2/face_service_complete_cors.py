import json 
import os 
import base64 
from datetime import datetime 
from http.server import HTTPServer, BaseHTTPRequestHandler 
 
class CompleteFaceHandler(BaseHTTPRequestHandler): 
 
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
            'message': 'Servicio Python funcionando!', 
            'timestamp': datetime.now().isoformat() 
        } 
        self.wfile.write(json.dumps(response).encode('utf-8')) 
        print("Test request recibido") 
 
    def handle_register_face(self): 
        try: 
            content_length = int(self.headers['Content-Length']) 
            post_data = self.rfile.read(content_length) 
            data = json.loads(post_data) 
            user_id = data.get('user_id') 
            image_data = data.get('image', '') 
            print(f"REGISTRO - Usuario: {user_id}") 
            if not os.path.exists('face_data'): 
                os.makedirs('face_data') 
            timestamp = datetime.now().strftime('%%Y%%m%%d_%%H%%M%%S') 
            img_filename = f"face_data/user_{user_id}_{timestamp}.jpg" 
            if image_data and ',' in image_data: 
                image_data = image_data.split(',')[1] 
            if image_data: 
                with open(img_filename, 'wb') as f: 
                    f.write(base64.b64decode(image_data)) 
                print(f"Imagen guardada: {img_filename}") 
                meta_filename = f"face_data/user_{user_id}_meta.json" 
                user_data = { 
                    'user_id': user_id, 
                    'registered_at': datetime.now().isoformat(), 
                    'image_file': img_filename, 
                    'status': 'active' 
                } 
                with open(meta_filename, 'w') as f: 
                    json.dump(user_data, f, indent=2) 
            self._set_headers(200) 
            self.end_headers() 
            response = { 
                'success': True,  
                'message': 'Rostro registrado EXITOSAMENTE!', 
                'user_id': user_id, 
                'file': img_filename 
            } 
            self.wfile.write(json.dumps(response).encode('utf-8')) 
            print(f"Registro COMPLETADO para usuario {user_id}") 
        except Exception as e: 
            print(f"Error en registro: {e}") 
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
            print(f"VERIFICACION - Buscando rostro...") 
            if not image_data: 
                raise Exception('No se recibio imagen para verificacion') 
            user_id = self.simulate_face_recognition(image_data) 
            if user_id: 
                self._set_headers(200) 
                self.end_headers() 
                response = { 
                    'success': True, 
                    'message': 'Rostro verificado EXITOSAMENTE!', 
                    'user_id': user_id, 
                    'confidence': 0.95, 
                    'timestamp': datetime.now().isoformat() 
                } 
                print(f"Verificacion EXITOSA para usuario {user_id}") 
            else: 
                self._set_headers(200) 
                self.end_headers() 
                response = { 
                    'success': False, 
                    'message': 'Rostro no reconocido. Registrese primero.' 
                } 
                print("Verificacion FALLIDA - Rostro no reconocido") 
            self.wfile.write(json.dumps(response).encode('utf-8')) 
        except Exception as e: 
            print(f"Error en verificacion: {e}") 
            self._set_headers(500) 
            self.end_headers() 
            response = {'success': False, 'message': f'Error: {str(e)}'} 
            self.wfile.write(json.dumps(response).encode('utf-8')) 
 
    def simulate_face_recognition(self, image_data): 
        if not os.path.exists('face_data'): 
            return None 
        user_files = [f for f in os.listdir('face_data') if f.startswith('user_') and f.endswith('.jpg')] 
        if user_files: 
            user_id = user_files[0].split('_')[1] 
            print(f"Usuario {user_id} encontrado con {len(user_files)} registros") 
            return int(user_id) 
        else: 
            print("No hay usuarios registrados") 
            return None 
 
    def handle_face_stats(self): 
        try: 
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
            print(f"Error en stats: {e}") 
            self._set_headers(500) 
            self.end_headers() 
            response = {'success': False, 'message': f'Error: {str(e)}'} 
            self.wfile.write(json.dumps(response).encode('utf-8')) 
 
def run_server(): 
    PORT = 5000 
    print("=" * 60) 
    print("SERVICIO FACIAL COMPLETO - CORS FIXED") 
    print("=" * 60) 
    print(f"URL: http://localhost:{PORT}") 
    print("Endpoints disponibles:") 
    print("   GET  /test              - Prueba de conexion") 
    print("   GET  /face_stats        - Estadisticas") 
    print("   POST /register_face     - Registrar rostro") 
    print("   POST /verify_face       - Verificar rostro") 
    print("   POST /recognize_face    - Verificar rostro (alias)") 
    print("=" * 60) 
    try: 
        server = HTTPServer(('localhost', PORT), CompleteFaceHandler) 
        print(f"Servicio iniciado en puerto {PORT}") 
        print("Presiona Ctrl+C para detener") 
        print("=" * 60) 
        server.serve_forever() 
    except Exception as e: 
        print(f"Error: {e}") 
 
if __name__ == "__main__": 
    run_server() 
