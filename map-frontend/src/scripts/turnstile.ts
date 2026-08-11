type TurnstileApi = {
  execute: (widgetId: string) => void;
  remove: (widgetId: string) => void;
  render: (container: HTMLElement, options: TurnstileRenderOptions) => string;
};

type TurnstileRenderOptions = {
  sitekey: string;
  action: string;
  execution: 'execute';
  appearance: 'interaction-only';
  size: 'flexible';
  callback: (token: string) => void;
  'error-callback': () => void;
  'expired-callback': () => void;
  'timeout-callback': () => void;
};

declare global {
  interface Window {
    turnstile?: TurnstileApi;
  }
}

const siteKey = import.meta.env.PUBLIC_TURNSTILE_SITE_KEY?.trim() ?? '';
const scriptUrl = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
let scriptPromise: Promise<TurnstileApi> | null = null;

export async function requestTurnstileToken(form: HTMLFormElement, action: string): Promise<string> {
  if (siteKey === '') {
    throw new Error('Turnstile site key is not configured.');
  }

  const turnstile = await loadTurnstile();
  const container = document.createElement('div');
  container.dataset.turnstileChallenge = '';
  container.style.flexBasis = '100%';

  const submit = form.querySelector<HTMLButtonElement>('button[type="submit"]');

  if (submit?.parentElement) {
    submit.parentElement.insertBefore(container, submit);
  } else {
    form.append(container);
  }

  return new Promise<string>((resolve, reject) => {
    let widgetId = '';
    let settled = false;

    const finish = (token?: string) => {
      if (settled) {
        return;
      }

      settled = true;

      if (widgetId !== '') {
        turnstile.remove(widgetId);
      }

      container.remove();

      if (token) {
        resolve(token);
      } else {
        reject(new Error('Turnstile verification failed.'));
      }
    };

    widgetId = turnstile.render(container, {
      sitekey: siteKey,
      action,
      execution: 'execute',
      appearance: 'interaction-only',
      size: 'flexible',
      callback: (token) => finish(token),
      'error-callback': () => finish(),
      'expired-callback': () => finish(),
      'timeout-callback': () => finish(),
    });

    turnstile.execute(widgetId);
  });
}

function loadTurnstile(): Promise<TurnstileApi> {
  if (window.turnstile) {
    return Promise.resolve(window.turnstile);
  }

  if (scriptPromise) {
    return scriptPromise;
  }

  scriptPromise = new Promise<TurnstileApi>((resolve, reject) => {
    const existing = document.querySelector<HTMLScriptElement>(`script[src="${scriptUrl}"]`);
    const script = existing ?? document.createElement('script');

    const handleLoad = () => {
      if (window.turnstile) {
        resolve(window.turnstile);
      } else {
        scriptPromise = null;
        reject(new Error('Turnstile API did not initialize.'));
      }
    };

    const handleError = () => {
      scriptPromise = null;
      reject(new Error('Turnstile API could not be loaded.'));
    };

    script.addEventListener('load', handleLoad, { once: true });
    script.addEventListener('error', handleError, { once: true });

    if (!existing) {
      script.src = scriptUrl;
      script.async = true;
      script.defer = true;
      document.head.append(script);
    }
  });

  return scriptPromise;
}
