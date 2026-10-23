(() => {
  const config = window.MGDNotificationCenter;
  if (!config) return;
  const readKey = 'mgd-notification-public-read-v1';
  const remembered = new Set(JSON.parse(window.localStorage.getItem(readKey) || '[]'));
  const request = (url, options = {}) => fetch(url, { credentials: 'same-origin', ...options }).then((response) => response.ok ? response.json() : { items: [] });
  document.querySelectorAll('[data-mgd-notification-center]').forEach((root) => {
    const button = root.querySelector('button'); const panel = root.querySelector('section'); const list = root.querySelector('ul'); const empty = root.querySelector('.mgd-notification-center__empty'); const count = root.querySelector('.mgd-notification-center__count');
    Promise.all([request(config.publicUrl), config.loggedIn ? request(config.personalUrl, { headers: { 'X-WP-Nonce': config.nonce } }) : Promise.resolve({ items: [] })]).then(([publicFeed, personalFeed]) => {
      const items = [...publicFeed.items, ...personalFeed.items];
      const unread = items.filter((item) => item.read_at === undefined ? !remembered.has(item.id) : !item.read_at).length;
      count.textContent = String(unread); count.hidden = unread === 0;
      if (!items.length) return;
      empty.hidden = true;
      items.forEach((item) => {
        const element = document.createElement('li');
        element.innerHTML = `<strong></strong><span></span>`; element.querySelector('strong').textContent = item.title; element.querySelector('span').textContent = item.message.replace(/<[^>]*>/g, '');
        if (item.target_url) { const link = document.createElement('a'); link.href = item.target_url; link.append(element); list.append(link); } else list.append(element);
      });
      button.addEventListener('click', () => {
        const open = button.getAttribute('aria-expanded') !== 'true'; button.setAttribute('aria-expanded', String(open)); panel.hidden = !open;
        if (open) { publicFeed.items.forEach((item) => remembered.add(item.id)); window.localStorage.setItem(readKey, JSON.stringify([...remembered].slice(-100))); personalFeed.items.filter((item) => !item.read_at).forEach((item) => request(`${config.personalUrl}/${item.id}/read`, { method: 'POST', headers: { 'X-WP-Nonce': config.nonce } })); count.hidden = true; }
      });
    });
  });
})();
