/* Glocke: Panel mit X aktuellen, Modal „Alle anzeigen“, Alle gelesen, Ausblenden */
(() => {
  'use strict';
  const config = window.MGDNotificationCenter;
  if (!config) return;
  const limit = Math.max(1, Math.min(30, Number(config.visibleLimit) || 5));

  // öffentliche Meldungen: gelesen/ausgeblendet nur lokal im Browser
  const readKey = 'mgd-notification-public-read-v2';
  const hideKey = 'mgd-notification-public-hidden-v1';
  const load = key => {
    try { const v = JSON.parse(localStorage.getItem(key) || '[]'); return new Set(Array.isArray(v) ? v.filter(x => typeof x === 'string' && x.length < 160).slice(-500) : []); } catch { return new Set(); }
  };
  const save = (key, set) => { try { localStorage.setItem(key, JSON.stringify([...set].slice(-500))); return true; } catch { return false; } };
  const publicRead = load(readKey);
  const publicHidden = load(hideKey);
  const personalRead = new Set();
  const personalHidden = new Set();
  const views = [];

  const publicKey = item => String(item.id) + ':' + String(item.updated_at || item.published_at || '');
  const personal = item => Object.prototype.hasOwnProperty.call(item, 'read_at');
  const isRead = item => personal(item) ? Boolean(item.read_at) || personalRead.has(String(item.id)) : publicRead.has(publicKey(item));
  const isHidden = item => personal(item) ? personalHidden.has(String(item.id)) : publicHidden.has(publicKey(item));
  const refreshAll = () => views.forEach(fn => fn());

  // angemeldet + gleiche Origin > Nonce auch beim öffentlichen Feed, sonst gilt die Anfrage als anonym (z.B. Wartungsmodus sperrt sie)
  const sameOrigin = url => { try { return new URL(url, location.href).origin === location.origin; } catch { return false; } };
  const publicOptions = () => (config.loggedIn && config.nonce && sameOrigin(config.publicUrl) ? { headers: { 'X-WP-Nonce': config.nonce } } : {});
  const request = async (url, options = {}) => {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    if (!response.ok) throw new Error('Anfrage fehlgeschlagen');
    return response.json();
  };
  const post = path => request(config.personalUrl + path, { method: 'POST', headers: { 'X-WP-Nonce': config.nonce }, keepalive: true });

  async function markRead(item) {
    if (isRead(item)) return true;
    if (!personal(item)) { publicRead.add(publicKey(item)); save(readKey, publicRead); refreshAll(); return true; }
    try {
      const result = await post('/' + encodeURIComponent(String(item.id)) + '/read');
      if (result.success !== true) return false;
      personalRead.add(String(item.id)); refreshAll(); return true;
    } catch { return false; }
  }

  async function markAllRead(items) {
    items.filter(item => !personal(item)).forEach(item => publicRead.add(publicKey(item)));
    const publicOk = save(readKey, publicRead);
    let ok = true;
    // persönliche in einem Rutsch übern Server
    if (config.loggedIn && items.some(item => personal(item) && !isRead(item))) {
      try { const result = await post('/read-all'); ok = result.success === true; } catch { ok = false; }
      if (ok) items.filter(personal).forEach(item => personalRead.add(String(item.id)));
    }
    refreshAll();
    return { ok, publicOk };
  }

  async function hide(item) {
    if (!personal(item)) { publicHidden.add(publicKey(item)); publicRead.add(publicKey(item)); save(hideKey, publicHidden); save(readKey, publicRead); refreshAll(); return true; }
    try {
      const result = await post('/' + encodeURIComponent(String(item.id)) + '/dismiss');
      if (result.success !== true) return false;
      personalHidden.add(String(item.id)); refreshAll(); return true;
    } catch { return false; }
  }

  async function hideRead(items) {
    const read = items.filter(isRead);
    read.filter(item => !personal(item)).forEach(item => publicHidden.add(publicKey(item)));
    const publicOk = save(hideKey, publicHidden);
    let ok = true;
    if (config.loggedIn && read.some(personal)) {
      try { const result = await post('/dismiss-read'); ok = result.success === true; } catch { ok = false; }
      if (ok) read.filter(personal).forEach(item => personalHidden.add(String(item.id)));
    }
    refreshAll();
    return { ok, publicOk, count: read.length };
  }

  function safeURL(value) {
    try { const url = new URL(value, location.href); return value && ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url.href : ''; } catch { return ''; }
  }
  // Inhalt mit Links, aber nur erlaubte Tags (kommt eh schon wp_kses_post-gefiltert)
  const allowed = new Set(['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'UL', 'OL', 'LI', 'A', 'H3', 'H4', 'BLOCKQUOTE']);
  // komplett weg samt Inhalt
  const dropped = new Set(['SCRIPT', 'STYLE', 'TEMPLATE', 'IFRAME', 'OBJECT', 'EMBED', 'NOSCRIPT', 'SVG', 'MATH', 'TEXTAREA', 'SELECT']);
  function richText(html) {
    const source = new DOMParser().parseFromString('<div>' + String(html || '') + '</div>', 'text/html').body.firstChild;
    const out = document.createDocumentFragment();
    const walk = (node, target) => {
      node.childNodes.forEach(child => {
        if (child.nodeType === Node.TEXT_NODE) { target.append(child.textContent); return; }
        if (child.nodeType !== Node.ELEMENT_NODE || dropped.has(child.tagName.toUpperCase())) return;
        if (!allowed.has(child.tagName)) { walk(child, target); return; }
        const el = document.createElement(child.tagName.toLowerCase());
        if (child.tagName === 'A') {
          const href = safeURL(child.getAttribute('href') || '');
          if (!href) { walk(child, target); return; }
          el.href = href;
          if (new URL(href).origin !== location.origin) { el.target = '_blank'; el.rel = 'noopener noreferrer'; }
        }
        walk(child, el); target.append(el);
      });
    };
    if (source) walk(source, out);
    return out;
  }
  const plainText = html => {
    const d = document.createElement('div'); d.append(richText(html));
    const parts = []; const tw = document.createTreeWalker(d, NodeFilter.SHOW_TEXT);
    while (tw.nextNode()) parts.push(tw.currentNode.textContent);
    return parts.join(' ').replace(/\s+/g, ' ').replace(/ ([.,;:!?])/g, '$1').trim();
  };
  const formatDate = value => {
    const d = new Date(String(value || '').replace(' ', 'T') + 'Z');
    return isNaN(d) ? '' : d.toLocaleDateString(document.documentElement.lang || 'de-DE', { day: '2-digit', month: '2-digit', year: 'numeric' });
  };
  const el = (tag, cls, text) => { const n = document.createElement(tag); if (cls) n.className = cls; if (text !== undefined) n.textContent = text; return n; };

  document.querySelectorAll('[data-mgd-notification-center]').forEach(async root => {
    const bell = root.querySelector('.mgd-notification-center__button');
    const panel = root.querySelector('.mgd-notification-center__panel');
    const list = root.querySelector('.mgd-notification-center__list');
    const empty = root.querySelector('.mgd-notification-center__empty');
    const count = root.querySelector('.mgd-notification-center__count');
    const readAll = root.querySelector('.mgd-notification-center__read-all');
    const showAll = root.querySelector('.mgd-notification-center__show-all');
    const hideReadBtn = root.querySelector('.mgd-notification-center__hide-read');
    const status = root.querySelector('.mgd-notification-center__status');
    let items = [];
    let busy = false;
    let failed = false;
    let dialogKeys = null;

    const closePanel = () => { panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); };
    bell.addEventListener('click', () => {
      const open = bell.getAttribute('aria-expanded') !== 'true';
      panel.hidden = !open; bell.setAttribute('aria-expanded', String(open));
    });
    root.addEventListener('keydown', event => { if (event.key === 'Escape' && !panel.hidden) { closePanel(); bell.focus(); } });
    document.addEventListener('click', event => { if (!panel.hidden && !event.composedPath().includes(root)) closePanel(); });

    // Modal „Alle Benachrichtigungen“
    const dialog = el('dialog', 'mgd-notification-dialog');
    dialog.setAttribute('aria-labelledby', panel.id + '-all');
    const header = el('header');
    const heading = el('h2', '', 'Alle Benachrichtigungen'); heading.id = panel.id + '-all';
    const dialogReadAll = el('button', 'mgd-notification-dialog__read-all', 'Alle gelesen'); dialogReadAll.type = 'button';
    const dialogHideRead = el('button', 'mgd-notification-dialog__read-all', 'Gelesene ausblenden'); dialogHideRead.type = 'button';
    const closer = el('button', 'mgd-notification-dialog__close', '×'); closer.type = 'button'; closer.setAttribute('aria-label', 'Fenster schließen');
    header.append(heading, dialogReadAll, dialogHideRead, closer);
    const body = el('div', 'mgd-notification-dialog__body');
    const dialogStatus = el('p', 'mgd-notification-center__status'); dialogStatus.setAttribute('role', 'status'); dialogStatus.setAttribute('aria-live', 'polite');
    const dialogEmpty = el('p', 'mgd-notification-dialog__empty', 'Keine Benachrichtigungen.');
    const allList = el('div', 'mgd-notification-dialog__list');
    body.append(dialogStatus, dialogEmpty, allList);
    dialog.append(header, body); document.body.append(dialog);
    const closeDialog = () => { if (typeof dialog.close === 'function') dialog.close(); else { dialog.open = false; bell.focus(); } };
    closer.addEventListener('click', closeDialog);
    dialog.addEventListener('close', () => bell.focus());
    dialog.addEventListener('click', event => { if (event.target === dialog) closeDialog(); });

    // Meldung dahin, wo der Nutzer gerade ist (Fenster offen > Status im Fenster)
    const say = text => { (dialog.open ? dialogStatus : status).textContent = text; };
    function openDialog(focusItem) {
      render();
      closePanel();
      if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.open = true;
      const target = focusItem ? allList.querySelector('[data-key="' + CSS.escape(keyOf(focusItem)) + '"]') : null;
      if (target) { target.open = true; markRead(focusItem); target.querySelector('summary').focus(); target.scrollIntoView({ block: 'nearest' }); } else closer.focus();
    }
    const keyOf = item => (personal(item) ? 'p' : 'o') + publicKey(item);

    function hideButton(item) {
      const b = el('button', 'mgd-notification-center__hide', 'Ausblenden'); b.type = 'button';
      b.setAttribute('aria-label', 'Meldung ausblenden: ' + String(item.title || ''));
      b.addEventListener('click', async event => {
        event.preventDefault(); event.stopPropagation();
        if (!await hide(item)) say('Meldung konnte nicht ausgeblendet werden. Bitte erneut versuchen.');
      });
      return b;
    }

    function panelItem(item) {
      const li = el('li'); li.classList.toggle('is-read', isRead(item));
      const target = safeURL(item.target_url);
      const modal = item.open_in_modal === true || item.open_in_modal === 1 || item.open_in_modal === '1';
      const action = el(modal || !target ? 'button' : 'a', 'mgd-notification-center__item-action', String(item.title || ''));
      if (action.tagName === 'BUTTON') { action.type = 'button'; action.setAttribute('aria-haspopup', 'dialog'); } else action.href = target;
      action.addEventListener('click', () => {
        markRead(item).then(ok => { if (!ok) say('Lesestatus konnte nicht gespeichert werden.'); });
        if (action.tagName === 'BUTTON') openDialog(item);
      });
      const text = plainText(item.message);
      const message = el('span', 'mgd-notification-center__text', text.length > 140 ? text.slice(0, 140) + ' …' : text);
      const meta = el('small', 'mgd-notification-center__meta', [formatDate(item.published_at), isRead(item) ? 'Gelesen' : 'Neu'].filter(Boolean).join(' · '));
      const foot = el('div', 'mgd-notification-center__foot');
      foot.append(meta, hideButton(item));
      li.append(action, message, foot);
      return li;
    }

    function dialogItem(item) {
      const details = el('details', 'mgd-notification-dialog__item'); details.dataset.key = keyOf(item);
      details.classList.toggle('is-read', isRead(item));
      const summary = el('summary');
      summary.append(el('span', 'mgd-notification-dialog__title', String(item.title || '')), el('small', '', [formatDate(item.published_at), isRead(item) ? 'Gelesen' : 'Neu'].filter(Boolean).join(' · ')));
      const content = el('div', 'mgd-notification-dialog__content');
      content.append(richText(item.message));
      const target = safeURL(item.target_url);
      const actions = el('div', 'mgd-notification-dialog__actions');
      if (target) { const link = el('a', 'mgd-notification-dialog__link', 'Verlinkten Inhalt öffnen'); link.href = target; actions.append(link); }
      const hideBtn = el('button', 'mgd-notification-dialog__hide', 'Ausblenden'); hideBtn.type = 'button';
      hideBtn.addEventListener('click', async () => { if (!await hide(item)) say('Meldung konnte nicht ausgeblendet werden.'); });
      actions.append(hideBtn); content.append(actions);
      details.append(summary, content);
      details.addEventListener('toggle', () => { if (details.open) markRead(item); });
      return details;
    }

    function render() {
      const visible = items.filter(item => !isHidden(item));
      const unread = visible.filter(item => !isRead(item)).length;
      count.textContent = String(unread); count.hidden = unread === 0;
      bell.setAttribute('aria-label', 'Benachrichtigungen, ' + unread + ' ungelesen');
      readAll.disabled = dialogReadAll.disabled = busy || unread === 0;
      if (hideReadBtn) hideReadBtn.disabled = dialogHideRead.disabled = busy || visible.length === unread;
      empty.hidden = visible.length > 0;
      list.replaceChildren(...visible.slice(0, limit).map(panelItem));
      showAll.hidden = visible.length === 0;
      showAll.textContent = visible.length > limit ? 'Alle anzeigen (' + visible.length + ')' : 'Alle anzeigen';
      // Modal nur neu bauen wenn sich die Liste ändert, sonst nur Status anpassen (Fokus bleibt)
      const keys = visible.map(keyOf).join('|');
      if (keys !== dialogKeys) {
        const focusKey = dialog.contains(document.activeElement) ? document.activeElement.closest('details')?.dataset.key : null;
        const open = new Set([...allList.querySelectorAll('details[open]')].map(d => d.dataset.key));
        allList.replaceChildren(...visible.map(item => { const d = dialogItem(item); d.open = open.has(d.dataset.key); return d; }));
        dialogKeys = keys;
        if (focusKey) allList.querySelector('[data-key="' + CSS.escape(focusKey) + '"] summary')?.focus();
        else if (dialog.open && !dialog.contains(document.activeElement)) closer.focus();
      } else {
        visible.forEach(item => {
          const d = allList.querySelector('[data-key="' + CSS.escape(keyOf(item)) + '"]');
          if (!d) return;
          d.classList.toggle('is-read', isRead(item));
          d.querySelector('summary small').textContent = [formatDate(item.published_at), isRead(item) ? 'Gelesen' : 'Neu'].filter(Boolean).join(' · ');
        });
      }
      dialogEmpty.hidden = visible.length > 0;
    }
    views.push(render);

    async function onReadAll() {
      if (busy) return;
      busy = true; render(); say('Wird als gelesen markiert …');
      const result = await markAllRead(items.filter(item => !isHidden(item)));
      busy = false; render();
      say(!result.ok ? 'Einige Meldungen konnten nicht als gelesen gespeichert werden. Bitte erneut versuchen.' : result.publicOk ? 'Alle Meldungen sind gelesen.' : 'Alle Meldungen sind gelesen. Öffentliche Lesemarken gelten nur für diese Seitenansicht.');
    }
    async function onHideRead() {
      if (busy) return;
      busy = true; render();
      const result = await hideRead(items.filter(item => !isHidden(item)));
      busy = false; render();
      say(!result.ok ? 'Einige Meldungen konnten nicht ausgeblendet werden. Bitte erneut versuchen.' : (result.count === 1 ? '1 gelesene Meldung ausgeblendet.' : result.count + ' gelesene Meldungen ausgeblendet.'));
    }
    if (hideReadBtn) hideReadBtn.addEventListener('click', onHideRead);
    dialogHideRead.addEventListener('click', onHideRead);
    readAll.addEventListener('click', onReadAll);
    dialogReadAll.addEventListener('click', onReadAll);
    showAll.addEventListener('click', () => openDialog(null));

    const feeds = await Promise.allSettled([
      request(config.publicUrl, publicOptions()),
      config.loggedIn ? request(config.personalUrl, { headers: { 'X-WP-Nonce': config.nonce } }) : Promise.resolve({ items: [] })
    ]);
    failed = feeds.some(result => result.status === 'rejected');
    items = feeds.flatMap(result => result.status === 'fulfilled' && Array.isArray(result.value.items) ? result.value.items : []);
    items.sort((a, b) => String(b.published_at || '').localeCompare(String(a.published_at || '')));
    if (failed) say('Ein Teil der Benachrichtigungen konnte nicht geladen werden. Bitte die Seite neu laden.');
    render();
  });
})();
