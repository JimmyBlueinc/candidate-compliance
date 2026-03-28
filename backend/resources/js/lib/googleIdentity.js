let scriptPromise = null;

function ensureScript() {
  if (scriptPromise) return scriptPromise;

  scriptPromise = new Promise((resolve, reject) => {
    if (window.google?.accounts?.id) {
      resolve(window.google);
      return;
    }

    const existing = document.querySelector('script[data-google-identity="true"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(window.google));
      existing.addEventListener('error', () => reject(new Error('Failed to load Google Identity script.')));
      return;
    }

    const script = document.createElement('script');
    script.src = 'https://accounts.google.com/gsi/client';
    script.async = true;
    script.defer = true;
    script.dataset.googleIdentity = 'true';
    script.onload = () => resolve(window.google);
    script.onerror = () => reject(new Error('Failed to load Google Identity script.'));
    document.head.appendChild(script);
  });

  return scriptPromise;
}

export async function renderGoogleButton(container, clientId, onCredential, options = {}) {
  if (!container) return;
  if (!clientId) throw new Error('Google client ID is missing.');

  const google = await ensureScript();
  if (!google?.accounts?.id) {
    throw new Error('Google Identity API is unavailable.');
  }

  google.accounts.id.initialize({
    client_id: clientId,
    callback: (response) => {
      if (typeof onCredential === 'function') {
        onCredential(response?.credential || '');
      }
    },
    auto_select: false,
  });

  container.innerHTML = '';
  google.accounts.id.renderButton(container, {
    type: options.type || 'standard',
    theme: options.theme || 'outline',
    size: options.size || 'large',
    text: options.text || 'continue_with',
    shape: options.shape || 'pill',
    width: options.width || 320,
    logo_alignment: 'left',
  });
}

