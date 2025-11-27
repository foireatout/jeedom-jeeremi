#!/usr/bin/env python3
import requests, json, sys

API_BASE_URL = "https://remi2.urbanhello.com/parse"
PARSE_APP_ID = "jf1a0bADt5fq"

def login(username, password):
    url = f"{API_BASE_URL}/login"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "Content-Type": "application/json"}
    data = {"username": username, "password": password}
    r = requests.post(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

def user_info(session_token, user_object_id, attribute=None):
    url = f"{API_BASE_URL}/users/{user_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    r = requests.get(url, headers=headers)
    r.raise_for_status()
    return r.json()

def remi_info(session_token, remi_object_id, attribute=None):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    r = requests.get(url, headers=headers)
    r.raise_for_status()
    return r.json()

def set_luminosity(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    data = {"luminosity": int(level)}
    r = requests.put(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

def set_volume(session_token, remi_object_id, level):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    data = {"volume": int(level)}
    r = requests.put(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

def set_face(session_token, remi_object_id, face_name):
    # maps as pointer depending on face_name; simple implementation sets expression if possible
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    data = {"face": {"expression": face_name}}
    r = requests.put(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

def play_music(session_token, remi_object_id, filename):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    data = {"musicPath": f"{filename}:play"}
    r = requests.put(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

def stop_music(session_token, remi_object_id):
    url = f"{API_BASE_URL}/classes/Remi/{remi_object_id}"
    headers = {"X-Parse-Application-Id": PARSE_APP_ID, "X-Parse-Session-Token": session_token}
    data = {"musicPath": "pause:0"}
    r = requests.put(url, headers=headers, json=data)
    r.raise_for_status()
    return r.json()

# CLI dispatcher to be used by wrapper
if __name__ == "__main__":
    if len(sys.argv) < 2:
        print("Usage")
        sys.exit(1)
    cmd = sys.argv[1]
    try:
        if cmd == 'login':
            res = login(sys.argv[2], sys.argv[3]); print(json.dumps(res))
        elif cmd == 'user_info':
            res = user_info(sys.argv[2], sys.argv[3]); print(json.dumps(res))
        elif cmd == 'remi_info':
            res = remi_info(sys.argv[2], sys.argv[3]); print(json.dumps(res))
        elif cmd == 'set_luminosity':
            res = set_luminosity(sys.argv[2], sys.argv[3], int(sys.argv[4])); print(json.dumps(res))
        elif cmd == 'set_volume':
            res = set_volume(sys.argv[2], sys.argv[3], int(sys.argv[4])); print(json.dumps(res))
        elif cmd == 'set_face':
            res = set_face(sys.argv[2], sys.argv[3], sys.argv[4]); print(json.dumps(res))
        elif cmd == 'play_music':
            res = play_music(sys.argv[2], sys.argv[3], sys.argv[4]); print(json.dumps(res))
        elif cmd == 'stop_music':
            res = stop_music(sys.argv[2], sys.argv[3]); print(json.dumps(res))
        else:
            print("Unknown")
    except Exception as e:
        print(json.dumps({"error": str(e)}))
        sys.exit(1)