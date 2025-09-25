// Inicialización de CKEditor 5 (local con fallback a CDN)
export function initEditor(selector = '#contenido') {
  const mount = () => {
    const el = document.querySelector(selector);
    if (!el) return;
    if (window.ClassicEditor) {
      // Crear editor con soporte de subida (CKFinderUploadAdapter del build local)
      const base = (window.APP_URL ? window.APP_URL.replace(/\/$/, '') : '');
      const uploadUrl = base + '/api/upload.php';
      const csrf = String(window.CSRF_TOKEN || '');

      const config = {
        // Manejo de subida de imágenes (SimpleUploadAdapter)
        simpleUpload: {
          uploadUrl,
          withCredentials: true, // envía cookie de sesión
          headers: { 'X-CSRF-Token': csrf }
        }
      };

      ClassicEditor.create(el, config)
        .then(editor => {
          // Fallback: si el build no trae SimpleUploadAdapter, usamos un adaptador custom.
          try {
            const repo = editor.plugins.get('FileRepository');
            if (repo && !('SimpleUploadAdapter' in (window.ClassicEditor?.builtinPlugins || []).reduce((a,p)=>{a[p?.pluginName]=1;return a;},{}))) {
              repo.createUploadAdapter = loader => new SimpleFallbackUploadAdapter(loader, uploadUrl, csrf);
            }
          } catch (_) {}

          el.__ckInstance = editor;
          window.__CKEDITORS = window.__CKEDITORS || new Map();
          window.__CKEDITORS.set(selector, editor);
        })
        .catch(console.error);
    } else {
      console.error('CKEditor no disponible');
    }
  };
  if (!window.ClassicEditor) {
    const loadCdn = () => {
      const cdn = document.createElement('script');
      cdn.src = 'https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js';
      cdn.onload = mount;
      cdn.onerror = () => console.error('No se pudo cargar CKEditor desde CDN');
      document.head.appendChild(cdn);
    };
    const local = document.createElement('script');
    local.src = (window.APP_URL ? window.APP_URL.replace(/\/$/, '') : '') + '/assets/js/ckeditor.js';
    local.onload = mount;
    local.onerror = loadCdn;
    document.head.appendChild(local);
  } else {
    mount();
  }
}

// Adaptador de subida simple (fallback) usando fetch
class SimpleFallbackUploadAdapter {
  constructor(loader, uploadUrl, csrf) {
    this.loader = loader;
    this.uploadUrl = uploadUrl;
    this.csrf = csrf;
    this.abortController = new AbortController();
  }

  upload() {
    return this.loader.file.then(file => {
      const data = new FormData();
      data.append('upload', file);
      return fetch(this.uploadUrl, {
        method: 'POST',
        body: data,
        credentials: 'include', // envía cookie de sesión
        headers: { 'X-CSRF-Token': this.csrf },
        signal: this.abortController.signal
      })
      .then(res => {
        if (!res.ok) throw new Error('Error de subida: ' + res.status);
        return res.json();
      })
      .then(json => {
        const url = json && (json.url || json.default);
        if (!url) throw new Error('Respuesta inválida del servidor');
        return { default: url };
      });
    });
  }

  abort() {
    try { this.abortController.abort(); } catch (_) {}
  }
}