"""Read-only local review. Serves only design artifacts and existing shell assets."""
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import urlsplit,unquote
ROOT=Path(__file__).resolve().parents[3]
class Handler(SimpleHTTPRequestHandler):
    def translate_path(self,path):
        p=unquote(urlsplit(path).path)
        base=ROOT/'public/build' if p.startswith('/build/') else ROOT/'docs/design'
        rel=p[len('/build/'):] if p.startswith('/build/') else p.lstrip('/')
        target=(base/rel).resolve()
        return str(target if target.is_relative_to(base.resolve()) else base/'not-found')
ThreadingHTTPServer(('127.0.0.1',4327),Handler).serve_forever()
