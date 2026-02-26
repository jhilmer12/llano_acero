# test_recognition.py
import face_recognition
import cv2
import numpy as np
import os

print("🔍 INICIANDO PRUEBA DE RECONOCIMIENTO...")

# 1. Cargar imagen guardada
known_faces = []
known_names = []

if os.path.exists("face_data"):
    for file in os.listdir("face_data"):
        if file.endswith(('.jpg', '.png')):
            # Extraer nombre del archivo (sin extensión)
            name = os.path.splitext(file)[0]
            image_path = os.path.join("face_data", file)
            
            # Cargar y codificar imagen
            image = face_recognition.load_image_file(image_path)
            encodings = face_recognition.face_encodings(image)
            
            if encodings:
                known_faces.append(encodings[0])
                known_names.append(name)
                print(f"✅ Imagen cargada: {name} - Encoding: {len(encodings[0])} puntos")
            else:
                print(f"❌ No se detectó rostro en: {file}")

print(f"📊 Rostros conocidos cargados: {len(known_faces)}")

# 2. Probar con cámara
if known_faces:
    print("🎥 Iniciando cámara para prueba...")
    cap = cv2.VideoCapture(0)
    
    if not cap.isOpened():
        print("❌ No se pudo abrir la cámara")
    else:
        print("✅ Cámara abierta - Mira a la cámara...")
        ret, frame = cap.read()
        if ret:
            # Detectar rostros en frame actual
            face_locations = face_recognition.face_locations(frame)
            face_encodings = face_recognition.face_encodings(frame, face_locations)
            
            print(f"👤 Rostros detectados: {len(face_locations)}")
            
            for face_encoding in face_encodings:
                # Comparar con rostros conocidos
                matches = face_recognition.compare_faces(known_faces, face_encoding)
                name = "Desconocido"
                
                if True in matches:
                    first_match_index = matches.index(True)
                    name = known_names[first_match_index]
                    print(f"🎉 ¡RECONOCIDO! → {name}")
                else:
                    print("❌ No reconocido - Comparando distancias...")
                    
                    # Calcular distancias
                    face_distances = face_recognition.face_distance(known_faces, face_encoding)
                    best_match_index = np.argmin(face_distances)
                    
                    print(f"Distancias: {face_distances}")
                    if face_distances[best_match_index] < 0.6:  # Umbral de tolerancia
                        name = known_names[best_match_index]
                        print(f"🎉 Reconocido por distancia: {name} (distancia: {face_distances[best_match_index]:.2f})")
                    else:
                        print(f"❌ Distancia muy alta: {face_distances[best_match_index]:.2f}")
        
        cap.release()

print("🔚 Prueba completada")