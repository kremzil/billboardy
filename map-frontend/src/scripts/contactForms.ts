type ContactPayload = {
  source: string;
  name: string;
  email: string;
  phone: string;
  company: string;
  note: string;
  adType: string;
  region: string;
  budget: string;
  startDate: string;
  message: string;
  website: string;
  items: [];
};

const apiBase = import.meta.env.PUBLIC_BILLBOARDY_API_BASE ?? '/wp-json/billboardy/v1';

for (const form of document.querySelectorAll<HTMLFormElement>('[data-contact-form]')) {
  bindContactForm(form);
}

function bindContactForm(form: HTMLFormElement): void {
  if (form.dataset.contactBound === 'true') {
    return;
  }

  form.dataset.contactBound = 'true';
  const kind = form.dataset.contactKind ?? 'contact';
  const successSelector = form.dataset.successTarget;
  const success = successSelector ? document.querySelector<HTMLElement>(successSelector) : null;
  const submit = form.querySelector<HTMLButtonElement>('button[type="submit"]');
  const message =
    form.querySelector<HTMLElement>('[data-contact-message]') ??
    form.parentElement?.querySelector<HTMLElement>('[data-contact-message]');
  const gdpr = form.querySelector<HTMLInputElement>('input[name="gdpr"]');
  const gdprBox = document.getElementById('gdpr-box');
  const gdprCheck = document.getElementById('gdpr-check');
  const reset = success?.querySelector<HTMLButtonElement>('[data-contact-reset]');

  const updateSubmitState = () => {
    if (!submit) {
      return;
    }

    const email = form.querySelector<HTMLInputElement>('input[name="email"]')?.value.trim() ?? '';
    const name = form.querySelector<HTMLInputElement>('input[name="name"]')?.value.trim() ?? '';
    const gdprOk = gdpr ? gdpr.checked : true;
    const valid = kind === 'quick' ? email !== '' : name !== '' && email !== '' && gdprOk;
    submit.disabled = !valid;
  };

  const updateGdprVisual = () => {
    if (!gdpr || !gdprBox || !gdprCheck) {
      updateSubmitState();
      return;
    }

    if (gdpr.checked) {
      gdprBox.style.borderColor = 'var(--color-brand)';
      gdprBox.style.background = 'var(--color-brand)';
      gdprCheck.classList.remove('hidden');
    } else {
      gdprBox.style.borderColor = '#d1d5db';
      gdprBox.style.background = 'white';
      gdprCheck.classList.add('hidden');
    }

    updateSubmitState();
  };

  form.addEventListener('input', updateSubmitState);
  form.addEventListener('change', updateGdprVisual);
  reset?.addEventListener('click', () => {
    form.reset();
    setLoading(form, false);
    setMessage(message, '', 'muted');
    updateGdprVisual();
    showFormAgain(form);
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    void submitContactForm(form, message);
  });

  updateGdprVisual();
}

async function submitContactForm(form: HTMLFormElement, message?: HTMLElement | null): Promise<void> {
  const payload = formPayload(form);

  setLoading(form, true);
  setMessage(message, 'Odosielam...', 'muted');

  try {
    const response = await fetch(`${apiBase.replace(/\/$/, '')}/inquiries`, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    if (!response.ok) {
      throw new Error(`Contact request failed with status ${response.status}`);
    }

    handleSuccess(form, payload, message);
  } catch (error) {
    console.error(error);
    setMessage(message, 'Dopyt sa nepodarilo odoslať. Skúste to prosím neskôr.', 'error');
  } finally {
    setLoading(form, false);
  }
}

function formPayload(form: HTMLFormElement): ContactPayload {
  const formData = new FormData(form);

  return {
    source: form.dataset.contactKind ?? 'contact',
    name: field(formData, 'name'),
    email: field(formData, 'email'),
    phone: field(formData, 'phone'),
    company: field(formData, 'company'),
    note: field(formData, 'note'),
    adType: field(formData, 'adType'),
    region: field(formData, 'region'),
    budget: field(formData, 'budget'),
    startDate: field(formData, 'startDate'),
    message: field(formData, 'message'),
    website: field(formData, 'website'),
    items: [],
  };
}

function handleSuccess(form: HTMLFormElement, payload: ContactPayload, message?: HTMLElement | null): void {
  const successSelector = form.dataset.successTarget;
  const success = successSelector ? document.querySelector<HTMLElement>(successSelector) : null;

  if (success) {
    success.querySelector<HTMLElement>('[data-success-name]')!.textContent = payload.name || 'vám';
    success.querySelector<HTMLElement>('[data-success-email]')!.textContent = payload.email;
    form.classList.add('hidden');
    success.classList.remove('hidden');
    success.classList.add('flex');
    return;
  }

  form.reset();
  setMessage(message, 'Dopyt bol odoslaný. Ozveme sa vám čo najskôr.', 'success');
}

function showFormAgain(form: HTMLFormElement): void {
  const successSelector = form.dataset.successTarget;
  const success = successSelector ? document.querySelector<HTMLElement>(successSelector) : null;

  if (success) {
    success.classList.add('hidden');
    success.classList.remove('flex');
    form.classList.remove('hidden');
  }
}

function setLoading(form: HTMLFormElement, loading: boolean): void {
  const submit = form.querySelector<HTMLButtonElement>('button[type="submit"]');
  const idle = form.querySelector<HTMLElement>('[data-submit-idle]');
  const loader = form.querySelector<HTMLElement>('[data-submit-loading]');

  if (submit) {
    submit.disabled = loading || submit.disabled;

    if (!loading) {
      submit.disabled = false;
      form.dispatchEvent(new Event('input'));
    }
  }

  idle?.classList.toggle('hidden', loading);
  loader?.classList.toggle('hidden', !loading);
  loader?.classList.toggle('flex', loading);
}

function setMessage(node: HTMLElement | null | undefined, text: string, state: 'muted' | 'success' | 'error'): void {
  if (!node) {
    return;
  }

  node.textContent = text;
  node.dataset.state = state;
}

function field(formData: FormData, key: string): string {
  return String(formData.get(key) ?? '').trim();
}
