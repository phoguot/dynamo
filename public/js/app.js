// JS thuần, không bundler, không framework SPA (ADR-0002). Nạp cuối layout/layout.phtml.
(function () {
  const toggle = document.querySelector('[data-nav-toggle]');
  const yard = document.getElementById('app-yard');

  if (toggle && yard) {
    toggle.addEventListener('click', function () {
      const open = !yard.classList.contains('is-open');
      yard.classList.toggle('is-open', open);
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  function enhanceFilterDrawer() {
    const screen = document.querySelector('.screen--moduleList');
    const tabs = document.querySelector('.moduleTabs');

    if (!screen || !tabs || document.querySelector('.filterDrawer')) {
      return;
    }

    const filterForms = Array.from(screen.querySelectorAll('form')).filter(function (form) {
      return form.method.toLowerCase() === 'get'
        && !form.classList.contains('headerSearch')
        && form.closest('.filterDrawer') === null;
    });

    if (filterForms.length === 0) {
      return;
    }

    const movedNodes = [];
    filterForms.forEach(function (form) {
      const panel = form.closest('.panel');
      const source = panel && panel.querySelectorAll('form').length === 1 && !panel.querySelector('table')
        ? panel
        : form;

      if (!movedNodes.includes(source)) {
        movedNodes.push(source);
      }
    });

    const filterButton = document.createElement('button');
    filterButton.type = 'button';
    filterButton.className = 'filterToggle';
    filterButton.setAttribute('aria-label', 'Bộ lọc');
    filterButton.setAttribute('title', 'Bộ lọc');
    filterButton.setAttribute('aria-expanded', 'false');
    filterButton.innerHTML = '<span class="filterIcon" aria-hidden="true"><span></span></span>';
    tabs.appendChild(filterButton);

    const backdrop = document.createElement('div');
    backdrop.className = 'filterDrawerBackdrop';
    backdrop.hidden = true;

    const drawer = document.createElement('aside');
    drawer.className = 'filterDrawer';
    drawer.hidden = true;
    drawer.setAttribute('aria-label', 'Bộ lọc');

    const head = document.createElement('div');
    head.className = 'filterDrawer__head';

    const title = document.createElement('strong');
    title.textContent = 'Bộ lọc';

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'filterDrawer__close';
    close.textContent = 'Đóng';

    const body = document.createElement('div');
    body.className = 'filterDrawer__body';

    head.append(title, close);
    drawer.append(head, body);
    movedNodes.forEach(function (node) {
      body.appendChild(node);
    });
    document.body.append(backdrop, drawer);

    function setOpen(open) {
      if (open) {
        backdrop.hidden = false;
        drawer.hidden = false;
      }

      requestAnimationFrame(function () {
        backdrop.classList.toggle('is-open', open);
        drawer.classList.toggle('is-open', open);
      });
      filterButton.setAttribute('aria-expanded', open ? 'true' : 'false');

      if (!open) {
        window.setTimeout(function () {
          if (!drawer.classList.contains('is-open')) {
            backdrop.hidden = true;
            drawer.hidden = true;
          }
        }, 180);
      }
    }

    filterButton.addEventListener('click', function () {
      setOpen(!drawer.classList.contains('is-open'));
    });
    close.addEventListener('click', function () {
      setOpen(false);
    });
    backdrop.addEventListener('click', function () {
      setOpen(false);
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
        setOpen(false);
      }
    });
  }

  const mobileForms = window.matchMedia('(max-width: 720px)');
  const sectionNames = ['Thông tin chính', 'Bổ sung', 'Ghi chú'];

  function shouldSkipForm(form) {
    return form.dataset.mobileSectioned === '1'
      || form.method.toLowerCase() === 'get'
      || form.classList.contains('headerSearch')
      || form.classList.contains('logoutForm')
      || form.classList.contains('form-login');
  }

  function enhanceLongForms() {
    if (!mobileForms.matches) {
      return;
    }

    document.querySelectorAll('form').forEach(function (form) {
      if (shouldSkipForm(form)) {
        return;
      }

      const rows = Array.from(form.children).filter(function (child) {
        return child.classList && child.classList.contains('form-row');
      });

      if (rows.length < 7) {
        return;
      }

      form.dataset.mobileSectioned = '1';
      form.classList.add('form--mobileSections');

      const actionAnchor = Array.from(form.children).find(function (child) {
        return child.classList && child.classList.contains('form-actions');
      }) || null;
      const size = 5;
      let firstErrorSection = null;

      for (let index = 0; index < rows.length; index += size) {
        const chunk = rows.slice(index, index + size);
        const sectionIndex = Math.floor(index / size);
        const details = document.createElement('details');
        const summary = document.createElement('summary');
        const title = document.createElement('span');
        const count = document.createElement('small');
        const hasError = chunk.some(function (row) {
          return row.classList.contains('form-row--error');
        });

        details.className = 'mobileFormSection';
        if (sectionIndex === 0 || hasError) {
          details.open = true;
        }
        if (hasError && firstErrorSection === null) {
          firstErrorSection = details;
        }

        title.textContent = sectionNames[sectionIndex] || ('Phần ' + (sectionIndex + 1));
        count.textContent = chunk.length + ' trường';
        summary.append(title, count);
        details.appendChild(summary);

        chunk.forEach(function (row) {
          details.appendChild(row);
        });

        form.insertBefore(details, actionAnchor);
      }

      if (firstErrorSection !== null) {
        firstErrorSection.scrollIntoView({ block: 'start' });
      }
    });
  }

  enhanceFilterDrawer();
  enhanceLongForms();
})();
