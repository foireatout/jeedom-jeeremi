#!/python_venv python3
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
    data = {"username": username, "password": password}
    response = requests.post(url, headers=headers, json=data, timeout=10)
    response.raise_for_status()
    return response.json()

def list_remi_musics(session_token, remi_object_id):
    url = f"{API_BASE_URL}/classes/Music"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "X-Parse-Session-Token": session_token,
        "Content-Type": "application/json"
    }

    where = {
        "REMI": {
            "__type": "Pointer",
            "className": "Remi",
            "objectId": remi_object_id
        }
    }

    response = requests.get(url, headers=headers, params={"where": json.dumps(where)}, timeout=10)
    response.raise_for_status()
    data = response.json()

    results = []
    for music in data.get("results", []):
        if "name" in music:
            results.append({
                "name": music["name"],
                "path": music.get("path", "")
            })

    return sorted(results, key=lambda x: x["name"])

def list_events(session_token, remi_object_id):
    url = f"{API_BASE_URL}/classes/Event"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "X-Parse-Session-Token": session_token,
        "Content-Type": "application/json"
    }

    where = {
        "remi": {
            "__type": "Pointer",
            "className": "Remi",
            "objectId": remi_object_id
        }
    }

    response = requests.get(url, headers=headers, params={"where": json.dumps(where)}, timeout=10)
    response.raise_for_status()
    return response.json().get("results", [])


def update_event(session_token, event_id, payload_dict):
    url = f"{API_BASE_URL}/classes/Event/{event_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json=payload_dict, timeout=10)
    response.raise_for_status()
    return response.json()

def set_alarm_enabled(session_token, alarm_id, enabled):
    url = f"{API_BASE_URL}/classes/Event/{alarm_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "X-Parse-Session-Token": session_token,
        "Content-Type": "application/json"
    }
    payload = {
        "enabled": bool(int(enabled))
    }

    response = requests.put(url, headers=headers, json=payload, timeout=10)
    response.raise_for_status()
    return response.json()


def get_user_info(session_token, user_object_id, attribute=None):
    url = f"{API_BASE_URL}/users/{user_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers, timeout=10)
    response.raise_for_status()
    data = response.json()
    return data.get(attribute) if attribute else data


def get_remi_info(session_token, remi_object_id, attribute=None):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.get(url, headers=headers, timeout=10)
    response.raise_for_status()
    data = response.json()
    return data.get(attribute) if attribute else data


def set_remi_luminosity(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"luminosity": level}, timeout=10)
    response.raise_for_status()
    return response.json()
    
def set_remi_nightluminosity(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"light_min": level}, timeout=10)
    response.raise_for_status()
    return response.json()


def set_remi_volume(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"volume": level}, timeout=10)
    response.raise_for_status()
    return response.json()


FACE_MAP = {
    "sleepyFace": "rnAltoFwYC",
    "awakeFace": "fIjF0yWRxX",
    "blankFace": "GDaZOVdRqj",
    "semiAwakeFace": "9faiiPGBVv"
}
FACE_MAP_INV = {v: k for k, v in FACE_MAP.items()}


def get_current_face(session_token, remi_object_id):
    remi = get_remi_info(session_token, remi_object_id)
    face = remi.get("face", {})
    fid = face.get("objectId")
    return FACE_MAP_INV.get(fid, "UnknownFace")


def set_face_by_name(session_token, remi_object_id, face_name):
    if face_name not in FACE_MAP:
        raise Exception(f"Unknown face '{face_name}'")

    pointer = {
        "__type": "Pointer",
        "className": "Face",
        "objectId": FACE_MAP[face_name]
    }

    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {
        "X-Parse-Application-Id": PARSE_APP_ID,
        "Content-Type": "application/json",
        "X-Parse-Session-Token": session_token
    }
    response = requests.put(url, headers=headers, json={"face": pointer}, timeout=10)
    response.raise_for_status()
    return response.json()

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

if __name__ == "__main__":
    try:
        cmd = sys.argv[1]

        if cmd == "login":
            print(json.dumps(login(sys.argv[2], sys.argv[3])))

        elif cmd == "user_info":
            print(json.dumps(get_user_info(sys.argv[2], sys.argv[3])))

        elif cmd == "remi_info":
            print(json.dumps(get_remi_info(sys.argv[2], sys.argv[3])))

        elif cmd == "set_luminosity":
            print(json.dumps(set_remi_luminosity(sys.argv[2], sys.argv[3], int(sys.argv[4]))))

        elif cmd == "set_nightluminosity":
            print(json.dumps(set_remi_nightluminosity(sys.argv[2], sys.argv[3], int(sys.argv[4]))))

        elif cmd == "set_volume":
            print(json.dumps(set_remi_volume(sys.argv[2], sys.argv[3], int(sys.argv[4]))))

        elif cmd == "get_face":
            print(get_current_face(sys.argv[2], sys.argv[3]))

        elif cmd == "set_face":
            print(json.dumps(set_face_by_name(sys.argv[2], sys.argv[3], sys.argv[4])))

        elif cmd == "play_music":
            print(json.dumps(play_music(sys.argv[2], sys.argv[3], sys.argv[4])))

        elif cmd == "stop_music":
            print(json.dumps(stop_music(sys.argv[2], sys.argv[3])))

        elif cmd == "list_music":
            musics = list_remi_musics(sys.argv[2], sys.argv[3])
            print(json.dumps(musics))

        elif cmd == "list_events":
            events = list_events(sys.argv[2], sys.argv[3])
            print(json.dumps(events))

        elif cmd == "set_alarm_enabled":
            print(json.dumps(set_alarm_enabled(sys.argv[2], sys.argv[3], sys.argv[4])))

        elif cmd == "update_event":
            payload = json.loads(sys.argv[4])
            print(json.dumps(update_event(sys.argv[2], sys.argv[3], payload)))

        else:
            print("Commande inconnue")

    except Exception as e:
        print(json.dumps({
            "error": "API_ERROR",
            "message": str(e)
        }))
        sys.exit(1)