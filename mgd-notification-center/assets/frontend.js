/* Bewusste Leseaktionen, getrennte öffentliche/lokale und persönliche/serverseitige Zustände. */
(() => {
  'use strict';
  const config = window.MGDNotificationCenter;
  if (!config) return;
  const storageKey = 'mgd-notification-public-read-v2';
  let stored = [];
  try { const value = JSON.parse(localStorage.getItem(storageKey) || '[]'); if (Array.isArray(value)) stored = value.filter(v => typeof v === 'string' && v.length < 160).slice(-500); } catch {}
  const remembered = new Set(stored);
  const personalRead = new Set();
  const pending = new Map();
  const refreshers = [];
  let persistent = true;
  const publicKey = item => String(item.id) + ':' + String(item.updated_at || item.published_at || '');
  const personal = item => Object.prototype.hasOwnProperty.call(item, 'read_at');
  const isRead = item => personal(item) ? Boolean(item.read_at) || personalRead.has(String(item.id)) : remembered.has(publicKey(item));
  const request = async (url, options = {}) => {
    const response = await fetch(url, { credentials: 'same-origin', ...options });
    if (!response.ok) throw new Error('Anfrage fehlgeschlagen');
    return response.json();
  };
  function savePublic() {
    try { localStorage.setItem(storageKey, JSON.stringify([...remembered].slice(-500))); } catch { persistent = false; }
  }
  async function markRead(item) {
    if (isRead(item)) return true;
    if (!personal(item)) { remembered.add(publicKey(item)); savePublic(); refreshers.forEach(fn => fn()); return true; }
    const id = String(item.id);
    if (pending.has(id)) return pending.get(id);
    const operation = request(config.personalUrl + '/' + encodeURIComponent(id) + '/read', {
      method: 'POST', headers: { 'X-WP-Nonce': config.nonce }, keepalive: true
    }).then(result => {
      if (result.success !== true) return false;
      personalRead.add(id);
      refreshers.forEach(fn => fn());
      return true;
    }).catch(() => false).finally(() => pending.delete(id));
    pending.set(id, operation);
    return operation;
  }
  const plainText = text => String(text || '').replace(/<[^>]*>/g, '');
  function safeURL(value) {
    try { const url = new URL(value, location.href); return value && ['http:', 'https:'].includes(url.protocol) && !url.username && !url.password ? url.href : ''; } catch { return ''; }
  }
  document.querySelectorAll('[data-mgd-notification-center]').forEach(async root => {
    const bell = root.querySelector('.mgd-notification-center__button');
    const panel = root.querySelector('.mgd-notification-center__panel');
    const list = root.querySelector('.mgd-notification-center__list');
    const empty = root.querySelector('.mgd-notification-center__empty');
    const count = root.querySelector('.mgd-notification-center__count');
    const all = root.querySelector('.mgd-notification-center__read-all');
    const status = root.querySelector('.mgd-notification-center__status');
    const records = [];
    let busy = false;
    let failed = false;
    const closePanel = () => { panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); };
    bell.addEventListener('click', () => {
      const open = bell.getAttribute('aria-expanded') !== 'true';
      panel.hidden = !open; bell.setAttribute('aria-expanded', String(open));
    });
    root.addEventListener('keydown', event => {
      if (event.key === 'Escape' && !panel.hidden) { closePanel(); bell.focus(); }
    });
    const dialog = document.createElement('dialog');
    dialog.className = 'mgd-notification-dialog';
    dialog.setAttribute('aria-label', 'Benachrichtigungsinhalt');
    const header = document.createElement('header');
    const title = document.createElement('h2');
    const closer = document.createElement('button');
    closer.type = 'button'; closer.textContent = '×'; closer.setAttribute('aria-label', 'Benachrichtigung schließen');
    header.append(title, closer);
    const body = document.createElement('div'); body.className = 'mgd-notification-dialog__body';
    dialog.append(header, body); root.append(dialog);
    const closeDialog = () => { if (typeof dialog.close === 'function') dialog.close(); else { dialog.open = false; bell.focus(); } };
    closer.addEventListener('click', closeDialog);
    dialog.addEventListener('close', () => bell.focus());
    dialog.addEventListener('keydown', event => {
      if (event.key === 'Escape' && typeof dialog.showModal !== 'function') { closeDialog(); return; }
      if (event.key !== 'Tab') return;
      const controls = [...dialog.querySelectorAll('button,a[href]')];
      if (event.shiftKey && document.activeElement === controls[0]) { event.preventDefault(); controls.at(-1).focus(); }
      else if (!event.shiftKey && document.activeElement === controls.at(-1)) { event.preventDefault(); controls[0].focus(); }
    });
    function showDialog(item) {
      title.textContent = String(item.title || '');
      const message = document.createElement('p'); message.textContent = plainText(item.message);
      body.replaceChildren(message);
      const target = safeURL(item.target_url);
      if (target) { const link = document.createElement('a'); link.href = target; link.textContent = 'Verlinkten Inhalt öffnen'; body.append(link); }
      closePanel();
      if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.open = true;
      closer.focus();
    }
    function refresh() {
      const unread = records.filter(record => !isRead(record.item)).length;
      count.textContent = String(unread); count.hidden = unread === 0;
      bell.setAttribute('aria-label', 'Benachrichtigungen, ' + unread + ' ungelesen');
      records.forEach(record => { record.element.classList.toggle('is-read', isRead(record.item)); record.readLabel.hidden = !isRead(record.item); });
      all.disabled = busy || failed || unread === 0;
    }
    refreshers.push(refresh);
    all.addEventListener('click', async () => {
      if (busy || failed) return;
      busy = true; refresh(); status.textContent = 'Meldungen werden als gelesen markiert …';
      let ok = true;
      // Bewusst begrenzte, sequenzielle Nutzung der bestehenden persönlichen Route.
      for (const record of records) if (!await markRead(record.item)) ok = false;
      busy = false; refresh();
      status.textContent = !ok ? 'Einige Meldungen konnten nicht als gelesen gespeichert werden. Bitte erneut versuchen.' : persistent ? 'Alle angezeigten Meldungen sind gelesen.' : 'Alle angezeigten Meldungen sind gelesen; öffentliche Lesemarken gelten nur für diese Seitenansicht.';
    });
    const feeds = await Promise.allSettled([
      request(config.publicUrl),
      config.loggedIn ? request(config.personalUrl, { headers: { 'X-WP-Nonce': config.nonce } }) : Promise.resolve({ items: [] })
    ]);
    failed = feeds.some(result => result.status === 'rejected');
    const items = feeds.flatMap(result => result.status === 'fulfilled' && Array.isArray(result.value.items) ? result.value.items : []);
    items.sort((a, b) => String(b.published_at || '').localeCompare(String(a.published_at || '')));
    empty.hidden = items.length > 0;
    if (failed) status.textContent = 'Ein Teil der Benachrichtigungen konnte nicht geladen werden. Bitte die Seite neu laden.';
    items.forEach(item => {
      const element = document.createElement('li');
      const target = safeURL(item.target_url);
      const modal = item.open_in_modal === true || item.open_in_modal === 1 || item.open_in_modal === '1';
      const action = document.createElement(modal || !target ? 'button' : 'a');
      if (action.tagName === 'BUTTON') action.type = 'button'; else action.href = target;
      action.className = 'mgd-notification-center__item-action';
      action.textContent = String(item.title || '');
      if (modal) action.setAttribute('aria-haspopup', 'dialog');
      const message = document.createElement('span'); const text = plainText(item.message);
      message.textContent = modal ? text.slice(0, 280) : text;
      const readLabel = document.createElement('small'); readLabel.textContent = 'Gelesen'; readLabel.hidden = true;
      action.addEventListener('click', () => {
        markRead(item).then(ok => { if (!ok) status.textContent = 'Lesestatus konnte nicht gespeichert werden.'; });
        if (modal) showDialog(item);
      });
      element.append(action, message, readLabel); list.append(element);
      records.push({ item, element, readLabel });
    });
    refresh();
  });
})();
