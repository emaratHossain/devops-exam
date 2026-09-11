import os
import socket
import time
from http.server import BaseHTTPRequestHandler, HTTPServer

PORT = int(os.environ.get("PORT", "5077"))
hung = False


class Handler(BaseHTTPRequestHandler):

    def do_GET(self):
        global hung

        if self.path == "/crash":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"bye")
            os._exit(1)

        if self.path == "/hang":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"now hanging")
            hung = True
            return

        if hung:
            while True:
                time.sleep(60)

        if self.path == "/":
            message = f"Hello from port {PORT}, hostname {socket.gethostname()}"
            self.send_response(200)
            self.end_headers()
            self.wfile.write(message.encode())

        elif self.path == "/api/notes":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"Notes API OK")

        elif self.path == "/healthz":
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"OK")

        elif self.path == "/slow":
            time.sleep(45)
            self.send_response(200)
            self.end_headers()
            self.wfile.write(b"finally")

        else:
            self.send_response(404)
            self.end_headers()


server = HTTPServer(("0.0.0.0", PORT), Handler)

print(f"Starting app on port {PORT}")

server.serve_forever()