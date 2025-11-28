#!/usr/bin/env python3
import requests
import json
import sys

API_BASE_URL = "https://remi2.urbanhello.com/parse"
PARSE_APP_ID = "jf1a0bADt5fq"

def login(username, password):
    url = f"{API_BASE_URL}/login"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json"
    }
    data = {
        "username": username,
        "password": password
    }
    response = requests.post(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def get_user_info(session_token, user_object_id, attribute=None):
    url = f"{API_BASE_URL}/users/{user_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    user_info = response.json()
    if attribute:
        return user_info.get(attribute, f"Attribute '{attribute}' not found")
    return user_info

def list_alarms(session_token, remi_object_id):
    alarms = get_alarms(session_token, remi_object_id)
    return alarms

def modify_alarm(session_token, remi_object_id, index, field, value):
    remi_info = get_remi_info(session_token, remi_object_id)
    alarms = remi_info.get("alarms", [])

    if index < 0 or index >= len(alarms):
        return {"error": f"Invalid alarm index {index}"}

    if value in ["true", "True", "1"]:
        value = True
    if value in ["false", "False", "0"]:
        value = False

    if value.isdigit():
        value = int(value)

    alarms[index][field] = value

    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    data = {"alarms": alarms}

    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def get_remi_info(session_token, remi_object_id, attribute=None):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers)
    response.raise_for_status()
    remi_info = response.json()
    if attribute:
        return remi_info.get(attribute, f"Attribute '{attribute}' not found")
    return remi_info

def set_remi_luminosity(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    data = {"luminosity": level}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def set_remi_volume(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    data = {"volume": level}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()


def set_face_expression(session_token, remi_object_id, expression):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    remi_info = get_remi_info(session_token, remi_object_id)
    face_info = remi_info.get("face", {})
    face_info["expression"] = expression
    data = {"face": face_info}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def get_alarms(session_token, remi_object_id):
    remi_info = get_remi_info(session_token, remi_object_id)
    return remi_info.get("alarms", [])

def get_temperature(session_token, remi_object_id):
    remi_info = get_remi_info(session_token, remi_object_id, "temp")
    return remi_info

# ============================
#  NOUVELLES FONCTIONS FACE
# ============================

FACE_MAP = {
    "sleepyFace": "rnAltoFwYC",
    "awakeFace": "fIjF0yWRxX",
    "blankFace": "GDaZOVdRqj",
    "semiAwakeFace": "9faiiPGBVv"
}

FACE_MAP_INV = {v: k for k, v in FACE_MAP.items()}

def get_face_name_from_id(face_object_id):
    return FACE_MAP_INV.get(face_object_id, "UnknownFace")

def get_current_face(session_token, remi_object_id):
    remi_info = get_remi_info(session_token, remi_object_id)
    face = remi_info.get("face", None)

    if not face:
        return "NoFace"

    if isinstance(face, dict) and face.get("__type") == "Pointer" and "objectId" in face:
        fid = face.get("objectId")
        return get_face_name_from_id(fid)

    if isinstance(face, dict):
        expression = face.get("expression")
        if expression:
            if expression in FACE_MAP_INV:
                return FACE_MAP_INV[expression]
            if expression in FACE_MAP:
                return expression
        if "objectId" in face:
            fid = face.get("objectId")
            return get_face_name_from_id(fid)

    return "UnknownFace"

def set_face_by_name(session_token, remi_object_id, face_name):
    if face_name not in FACE_MAP:
        return {"error": f"Unknown face '{face_name}'"}

    target_face_id = FACE_MAP[face_name]

    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }

    pointer = {
        "__type": "Pointer",
        "className": "Face",
        "objectId": target_face_id
    }
    data = {"face": pointer}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

# ============================
#  MUSIQUE (NOUVELLES FONCTIONS)
# ============================

def play_music(session_token, remi_object_id, filename):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    data = {"musicPath": f"{filename}:play"}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def stop_music(session_token, remi_object_id):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    data = {"musicPath": "pause:0"}
    response = requests.put(url, headers=headers, json=data)
    response.raise_for_status()
    return response.json()

def get_music_path(session_token, remi_object_id):
    return get_remi_info(session_token, remi_object_id, "musicPath")

def get_music_mode(session_token, remi_object_id):
    return get_remi_info(session_token, remi_object_id, "musicMode")

# ============================
#  DISPATCHER ARGUMENTS
# ============================

if __name__ == "__main__":
    if len(sys.argv) > 1 and sys.argv[1] == "login":
        username = sys.argv[2]
        password = sys.argv[3]
        result = login(username, password)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "user_info":
        session_token = sys.argv[2]
        user_object_id = sys.argv[3]
        attribute = sys.argv[4] if len(sys.argv) > 4 else None
        result = get_user_info(session_token, user_object_id, attribute)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "remi_info":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        attribute = sys.argv[4] if len(sys.argv) > 4 else None
        result = get_remi_info(session_token, remi_object_id, attribute)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "set_luminosity":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        level = int(sys.argv[4])
        result = set_remi_luminosity(session_token, remi_object_id, level)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "set_volume":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        level = int(sys.argv[4])
        result = set_remi_volume(session_token, remi_object_id, level)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "set_face_expression":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        expression = sys.argv[4]
        result = set_face_expression(session_token, remi_object_id, expression)
        print(json.dumps(result))

    elif len(sys.argv) > 1 and sys.argv[1] == "get_alarms":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        alarms = get_alarms(session_token, remi_object_id)
        print(json.dumps(alarms))

    elif len(sys.argv) > 1 and sys.argv[1] == "get_temperature":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        temp = get_temperature(session_token, remi_object_id)
        print(f"Température: {temp}")

    elif len(sys.argv) > 1 and sys.argv[1] == "get_face":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        result = get_current_face(session_token, remi_object_id)
        print(result)

    elif len(sys.argv) > 1 and sys.argv[1] == "set_face":
        session_token = sys.argv[2]
        remi_object_id = sys.argv[3]
        face_name = sys.argv[4]
        result = set_face_by_name(session_token, remi_object_id, face_name)
        print(json.dumps(result))

    elif sys.argv[1] == "alarms":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        print(json.dumps(list_alarms(session_token, remi_id)))

    elif sys.argv[1] == "set_alarm":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        index = int(sys.argv[4])
        field = sys.argv[5]
        value = sys.argv[6]
        print(json.dumps(modify_alarm(session_token, remi_id, index, field, value)))

    # ===== MUSIQUE (DISPATCHER) =====
    elif sys.argv[1] == "play_music":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        filename = sys.argv[4]
        print(json.dumps(play_music(session_token, remi_id, filename)))

    elif sys.argv[1] == "stop_music":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        print(json.dumps(stop_music(session_token, remi_id)))

    elif sys.argv[1] == "music_path":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        print(get_music_path(session_token, remi_id))

    elif sys.argv[1] == "music_mode":
        session_token = sys.argv[2]
        remi_id = sys.argv[3]
        print(get_music_mode(session_token, remi_id))

    else:
        print("Usage: urbanhello_api.py [...]")