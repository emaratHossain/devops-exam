from http.server import BaseHTTPRequestHandler, HTTPServer
import json
import socket

PORT = 5076

class MyHandler(BaseHTTPRequestHandler):

    def do_GET(self):

        if self.path == "/":
            response = {
                "message": "Hello from emaapp from 5076",
                "port": PORT,
                "server_ip": socket.gethostbyname(socket.gethostname())
            }

        elif self.path == "/whoami":
            response = {
                "remoteAddress": self.client_address[0],
                "headers": dict(self.headers)
            }

        else:
            response = {
                "error": "Not found"
            }

        body = json.dumps(response, indent=2).encode()

        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(body)))
        self.end_headers()

        self.wfile.write(body)


server = HTTPServer(("0.0.0.0", PORT), MyHandler)

print(f"emaapp running on port {PORT}")

server.serve_forever()