// UI feedback básico
export function showToast(message, type = 'info') {
  const div = document.createElement('div');
  div.className = `alert alert-${type}`;
  div.textContent = message;
  document.querySelector('.container')?.prepend(div);
  setTimeout(() => div.remove(), 4000);
}