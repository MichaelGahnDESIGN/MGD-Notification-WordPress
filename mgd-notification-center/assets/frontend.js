(() => {
  const config = window.MGDNotificationCenter;
  if (!config) return;
  const readKey = 'mgd-notification-public-read-v1';
  let stored = [];
  try { stored = JSON.parse(window.localStorage.getItem(readKey) || '[]'); } catch { window.localStorage.removeItem(readKey); }
  const remembered = new Set(Array.isArray(stored) ? stored : []);
  const request = (url, options = {}) => fetch(url, { credentials: 'same-origin', ...options }).then((response) => response.ok ? response.json() : { items: [] });
  document.querySelectorAll('[data-mgd-notification-center]').forEach((root) => {
    const button = root.querySelector('button'); const panel = root.querySelector('section'); const list = root.querySelector('ul'); const empty = root.querySelector('.mgd-notification-center__empty'); const count = root.querySelector('.mgd-notification-center__count');
    Promise.all([request(config.publicUrl), config.loggedIn ? request(config.personalUrl, { headers: { 'X-WP-Nonce': config.nonce } }) : Promise.resolve({ items: [] })]).then(([publicFeed, personalFeed]) => {
      const items = [...publicFeed.items, ...personalFeed.items].sort((a, b) => String(b.published_at || '').localeCompare(String(a.published_at || '')));
      const unread = items.filter((item) => item.read_at === undefined ? !remembered.has(item.id) : !item.read_at).length;
      count.textContent = String(unread); count.hidden = unread === 0;
      if (!items.length) return;
      empty.hidden = true;
      items.forEach((item) => {
        const element = document.createElement('li');
        element.innerHTML = `<strong></strong><span></span>`; element.querySelector('strong').textContent = item.title; element.querySelector('span').textContent = item.message.replace(/<[^>]*>/g, '');
        const personal = Object.prototype.hasOwnProperty.call(item, 'read_at');
        const markPersonalRead = () => {
          if (!personal || item.read_at) return;
          item.read_at = 'pending';
          request(`${config.personalUrl}/${item.id}/read`, { method: 'POST', headers: { 'X-WP-Nonce': config.nonce }, keepalive: true });
        };
        if (item.target_url) {
          const link = document.createElement('a'); link.href = item.target_url;
          if (personal) link.addEventListener('click', markPersonalRead);
          link.append(element); list.append(link);
        } else {
          if (personal) {
            element.tabIndex = 0; element.setAttribute('role', 'button'); element.setAttribute('aria-label', `${item.title}: als gelesen markieren`);
            element.addEventListener('click', markPersonalRead);
            element.addEventListener('keydown', (event) => { if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); markPersonalRead(); } });
          }
          list.append(element);
        }
      });
      button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true'; button.setAttribute('aria-expanded', String(open)); panel.hidden = !open;
        if (open) { publicFeed.items.forEach((item) => remembered.add(item.id)); window.localStorage.setItem(readKey, JSON.stringify([...remembered].slice(-100))); count.hidden = true; }
      });
      root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); button.focus(); }
      });
    });
  });
})();
