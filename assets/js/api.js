// Helper para peticiones fetch con CSRF y JSON.
// Maneja errores devolviendo mensajes amigables.
export async function apiFetch(url, options = {}) {
  const defaults = {
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': window.CSRF_TOKEN || ''
    },
    credentials: 'same-origin'
  };
  const opts = { ...defaults, ...options };
  const res = await fetch(url, opts);
  const contentType = res.headers.get('Content-Type') || '';
  const isJson = contentType.includes('application/json');
  if (!res.ok) {
    const err = isJson ? await res.json() : { error: await res.text() };
    throw new Error(err.error || 'Error en la solicitud');
  }
  return isJson ? res.json() : res.text();
}