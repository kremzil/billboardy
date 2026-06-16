type InquiryItem = {
  id: string;
  code: string;
  title: string;
  mediaTypeLabel: string;
  locationLabel: string;
  sizeLabel: string;
  imageUrl: string;
};

type InquiryAddEvent = CustomEvent<InquiryItem>;

const storageKey = 'billboardy.inquiry.items';
const root = document.querySelector<HTMLElement>('[data-inquiry-widget]');
let lastFocused: HTMLElement | null = null;

if (root) {
  bootInquiry(root);
}

function bootInquiry(container: HTMLElement): void {
  const bar = mustGet<HTMLElement>('[data-inquiry-bar]', container);
  const count = mustGet<HTMLElement>('[data-inquiry-count]', container);
  const modal = mustGet<HTMLElement>('[data-inquiry-modal]', container);
  const itemsNode = mustGet<HTMLElement>('[data-inquiry-items]', container);
  const form = mustGet<HTMLFormElement>('[data-inquiry-form]', container);
  const message = mustGet<HTMLElement>('[data-inquiry-message]', container);
  const apiBase = container.dataset.apiBase ?? '/wp-json/billboardy/v1';

  const render = () => {
    const items = readItems();
    count.textContent = String(items.length);
    bar.hidden = items.length === 0;
    renderItems(itemsNode, items);
  };

  document.addEventListener('billboardy:inquiry-add', ((event: InquiryAddEvent) => {
    addItem(event.detail);
    render();
  }) as EventListener);

  container.addEventListener('click', (event) => {
    const target = event.target instanceof Element ? event.target : null;

    if (target?.closest('[data-inquiry-open]')) {
      openModal(modal);
    }

    if (target?.closest('[data-inquiry-close]')) {
      closeModal(modal);
    }

    const removeButton = target?.closest<HTMLElement>('[data-inquiry-remove]');

    if (removeButton) {
      removeItem(removeButton.dataset.inquiryRemove ?? '');
      render();
    }
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && !modal.hidden) {
      closeModal(modal);
    }
  });

  modal.addEventListener('keydown', (event) => {
    if (event.key !== 'Tab' || modal.hidden) {
      return;
    }

    const dialog = modal.querySelector<HTMLElement>('.bb-inquiry-dialog') ?? modal;
    const focusables = getFocusableElements(dialog);

    if (focusables.length === 0) {
      return;
    }

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    if (event.shiftKey) {
      if (active === first || !dialog.contains(active)) {
        event.preventDefault();
        last.focus();
      }
    } else if (active === last) {
      event.preventDefault();
      first.focus();
    }
  });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    void submitInquiry(form, message, apiBase, render, modal);
  });

  render();
}

function readItems(): InquiryItem[] {
  try {
    const parsed = JSON.parse(window.sessionStorage.getItem(storageKey) ?? '[]');

    if (!Array.isArray(parsed)) {
      return [];
    }

    return parsed.filter(isInquiryItem).slice(0, 20);
  } catch {
    return [];
  }
}

function writeItems(items: InquiryItem[]): void {
  window.sessionStorage.setItem(storageKey, JSON.stringify(items.slice(0, 20)));
}

function addItem(item: InquiryItem): void {
  if (!isInquiryItem(item)) {
    return;
  }

  const items = readItems();
  const next = [item, ...items.filter((current) => current.id !== item.id)];
  writeItems(next);
}

function removeItem(id: string): void {
  writeItems(readItems().filter((item) => item.id !== id));
}

function renderItems(node: HTMLElement, items: InquiryItem[]): void {
  if (items.length === 0) {
    node.innerHTML = '<p class="bb-inquiry-empty">Vyberte plochu na mape cez tlačidlo Mám záujem.</p>';
    return;
  }

  node.innerHTML = items.map((item) => `
    <article class="bb-inquiry-item">
      ${item.imageUrl ? `<img src="${escapeAttribute(item.imageUrl)}" alt="${escapeAttribute(item.title)}" loading="lazy" />` : '<div class="bb-inquiry-thumb"></div>'}
      <div>
        <strong>${escapeHtml(item.title)}</strong>
        <span>${escapeHtml(item.mediaTypeLabel || item.code)}${item.sizeLabel ? ` · ${escapeHtml(item.sizeLabel)}` : ''}</span>
        <small>${escapeHtml(item.locationLabel)}</small>
      </div>
      <button type="button" data-inquiry-remove="${escapeAttribute(item.id)}" aria-label="Odstrániť">
        ×
      </button>
    </article>
  `).join('');
}

async function submitInquiry(
  form: HTMLFormElement,
  message: HTMLElement,
  apiBase: string,
  render: () => void,
  modal: HTMLElement,
): Promise<void> {
  const items = readItems();

  if (items.length === 0) {
    showMessage(message, 'Vyberte aspoň jednu plochu.', 'error');
    return;
  }

  const submit = form.querySelector<HTMLButtonElement>('button[type="submit"]');
  const formData = new FormData(form);
  const payload = {
    name: String(formData.get('name') ?? '').trim(),
    email: String(formData.get('email') ?? '').trim(),
    phone: String(formData.get('phone') ?? '').trim(),
    company: String(formData.get('company') ?? '').trim(),
    note: String(formData.get('note') ?? '').trim(),
    website: String(formData.get('website') ?? '').trim(),
    items,
  };

  submit?.setAttribute('disabled', 'true');
  showMessage(message, 'Odosielam dopyt...', 'muted');

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
      throw new Error(`Inquiry failed with status ${response.status}`);
    }

    window.sessionStorage.removeItem(storageKey);
    form.reset();
    render();
    showMessage(message, 'Dopyt bol odoslaný. Ozveme sa vám čo najskôr.', 'success');
    window.setTimeout(() => closeModal(modal), 1200);
  } catch (error) {
    console.error(error);
    showMessage(message, 'Dopyt sa nepodarilo odoslať. Skúste to prosím neskôr.', 'error');
  } finally {
    submit?.removeAttribute('disabled');
  }
}

function openModal(modal: HTMLElement): void {
  lastFocused = document.activeElement instanceof HTMLElement ? document.activeElement : null;
  modal.hidden = false;
  document.documentElement.classList.add('bb-inquiry-open');
  const dialog = modal.querySelector<HTMLElement>('.bb-inquiry-dialog') ?? modal;
  const firstField = modal.querySelector<HTMLInputElement>('input[name="name"]');
  (firstField ?? getFocusableElements(dialog)[0])?.focus();
}

function closeModal(modal: HTMLElement): void {
  modal.hidden = true;
  document.documentElement.classList.remove('bb-inquiry-open');

  if (lastFocused && lastFocused.isConnected && lastFocused.getClientRects().length > 0) {
    lastFocused.focus();
  }

  lastFocused = null;
}

function getFocusableElements(scope: HTMLElement): HTMLElement[] {
  const selector = 'a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';

  return Array.from(scope.querySelectorAll<HTMLElement>(selector)).filter(
    (element) => element.getClientRects().length > 0,
  );
}

function showMessage(node: HTMLElement, text: string, state: 'muted' | 'success' | 'error'): void {
  node.textContent = text;
  node.dataset.state = state;
}

function isInquiryItem(value: unknown): value is InquiryItem {
  if (!value || typeof value !== 'object') {
    return false;
  }

  const item = value as Record<string, unknown>;
  return typeof item.id === 'string' && typeof item.title === 'string';
}

function mustGet<T extends Element>(selector: string, scope: ParentNode): T {
  const node = scope.querySelector<T>(selector);

  if (!node) {
    throw new Error(`Missing required element: ${selector}`);
  }

  return node;
}

function escapeHtml(value: string): string {
  return value.replace(/[&<>"']/g, (char) => {
    const entities: Record<string, string> = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;',
    };

    return entities[char];
  });
}

function escapeAttribute(value: string): string {
  return escapeHtml(value).replace(/`/g, '&#096;');
}
