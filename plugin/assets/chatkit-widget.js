(function () {
  var configs = window.PCSC_CHATKIT_CONFIGS || null;
  if (!configs || typeof configs !== 'object') return;

  var roots = document.querySelectorAll('[data-chatkit-root]');
  if (!roots || !roots.length) return;

  function postWithTimeout(url, fetchOpts, timeoutMs) {
    var controller = new AbortController();
    var timer = setTimeout(function () {
      controller.abort();
    }, timeoutMs || 15000);

    fetchOpts = fetchOpts || {};
    fetchOpts.signal = controller.signal;
    fetchOpts.cache = 'no-store';
    fetchOpts.credentials = 'include';

    return fetch(url, fetchOpts).finally(function () {
      clearTimeout(timer);
    });
  }

  roots.forEach(function (root) {
    var elId = root.getAttribute('data-chatkit-root');
    if (!elId) return;

    var cfg = configs[elId];
    if (!cfg) return;

    var ajaxUrl = cfg.ajaxUrl || null;
    var ajaxAction = cfg.ajaxAction || 'pcsc_chatkit_client_secret';
    var endpoint = cfg.endpoint || null;

    var openBtn = root.querySelector('[data-chatkit-open]');
    var panel = root.querySelector('[data-chatkit-panel]');
    var closeBtn = root.querySelector('[data-chatkit-close]');
    var loading = root.querySelector('[data-chatkit-loading]');
    var chatEl = document.getElementById(elId);

    var initialized = false;
    var initInProgress = false;

    function showLoading(message) {
      if (!loading) return;
      loading.style.display = 'flex';
      loading.textContent = message || 'Loading chat…';
    }

    function hideLoading() {
      if (!loading) return;
      loading.style.display = 'none';
    }

    function showError(message) {
      if (!loading) return;
      loading.style.display = 'flex';
      loading.textContent = message || 'Chat failed to load. Please try again.';
    }

    async function fetchClientSecret(currentClientSecret) {
      showLoading('Connecting…');

      try {
        if (ajaxUrl) {
          var body =
            'action=' + encodeURIComponent(ajaxAction) +
            '&current_client_secret=' + encodeURIComponent(currentClientSecret || '');

          var ajaxResponse = await postWithTimeout(
            ajaxUrl,
            {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json'
              },
              body: body
            },
            15000
          );

          if (!ajaxResponse.ok) {
            var ajaxText = '';
            try { ajaxText = await ajaxResponse.text(); } catch (e) {}
            console.error('[PCSC ChatKit] admin-ajax failed', ajaxResponse.status, ajaxText);
            showError('Unable to connect chat right now.');
            throw new Error('admin-ajax failed: ' + ajaxResponse.status);
          }

          var ajaxData = await ajaxResponse.json();

          if (!ajaxData || !ajaxData.success || !ajaxData.data || !ajaxData.data.client_secret) {
            console.error('[PCSC ChatKit] Invalid admin-ajax response', ajaxData);
            showError('Unable to start chat.');
            throw new Error('Invalid admin-ajax response');
          }

          return ajaxData.data.client_secret;
        }

        if (endpoint) {
          var restResponse = await postWithTimeout(
            endpoint,
            {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                current_client_secret: currentClientSecret || null
              })
            },
            15000
          );

          if (!restResponse.ok) {
            var restText = '';
            try { restText = await restResponse.text(); } catch (e) {}
            console.error('[PCSC ChatKit] REST failed', restResponse.status, restText);
            showError('Unable to connect chat right now.');
            throw new Error('REST failed: ' + restResponse.status);
          }

          var restData = await restResponse.json();

          if (!restData || !restData.client_secret) {
            console.error('[PCSC ChatKit] Invalid REST response', restData);
            showError('Unable to start chat.');
            throw new Error('Invalid REST response');
          }

          return restData.client_secret;
        }

        showError('Chat configuration is missing.');
        throw new Error('Missing ajaxUrl/endpoint');
      } catch (error) {
        if (error && error.name === 'AbortError') {
          showError('Connection timed out. Please try again.');
        }
        return Promise.reject(error);
      }
    }

    function initChatkitOnce() {
      if (initialized || initInProgress) return;

      if (!chatEl) {
        chatEl = document.getElementById(elId);
      }

      if (!chatEl) {
        console.error('[PCSC ChatKit] element not found:', elId);
        showError('Chat UI element not found.');
        return;
      }

      initInProgress = true;
      showLoading('Loading chat…');

      if (!chatEl.__pcscErrorHooked) {
        chatEl.addEventListener('chatkit.error', function (event) {
          var err = (event && event.detail && event.detail.error) ? event.detail.error : event;
          console.error('[PCSC ChatKit error]', err);
          showError('Chat failed. Please close and reopen.');
          initialized = false;
          initInProgress = false;
        });
        chatEl.__pcscErrorHooked = true;
      }

      try {
        chatEl.setOptions({
          api: {
            getClientSecret: function (currentClientSecret) {
              return fetchClientSecret(currentClientSecret);
            }
          },
          frameTitle: cfg.title || 'Tech Support Advisor',
          startScreen: {
            greeting: cfg.greeting || 'Aloha! How can I help you today?',
            prompts: Array.isArray(cfg.prompts) ? cfg.prompts : []
          },
          composer: {
            placeholder: cfg.placeholder || 'Chat with PCSC AI...'
          },
          disclaimer: {
            text: cfg.disclaimer || 'For urgent or more help, call PCSC at 808-742-2700.'
          },
          theme: {
            colorScheme: 'light',
            radius: 'round',
            density: 'normal'
          }
        });

        setTimeout(function () {
          initialized = true;
          initInProgress = false;
          hideLoading();
        }, 500);
      } catch (error) {
        console.error('[PCSC ChatKit] setOptions threw', error);
        showError('Chat failed to initialize.');
        initialized = false;
        initInProgress = false;
      }
    }

    async function openPanel() {
      if (!panel) return;

      panel.classList.add('is-open');

      if (initialized) {
        hideLoading();
        return;
      }

      showLoading('Loading chat…');

      if (window.customElements && customElements.whenDefined) {
        try {
          await customElements.whenDefined('openai-chatkit');
        } catch (e) {}
      }

      setTimeout(function () {
        initChatkitOnce();
      }, 80);
    }

    function closePanel() {
      if (!panel) return;
      panel.classList.remove('is-open');
      hideLoading();

    }

    function onViewportChange() {
      if (!panel || !panel.classList.contains('is-open')) return;
      setTimeout(function () {
        if (initialized) {
          hideLoading();
        }
      }, 200);
    }

    if (openBtn) {
      openBtn.addEventListener('click', function (e) {
        e.preventDefault();
        openPanel();
      });
    }

    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        closePanel();
      });
    }

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        closePanel();
      }
    });

    document.addEventListener('click', function (e) {
      if (!panel || !panel.classList.contains('is-open')) return;
      var clickedInsidePanel = panel.contains(e.target);
      var clickedOpenButton = openBtn && openBtn.contains(e.target);

      if (!clickedInsidePanel && !clickedOpenButton) {
        closePanel();
      }
    });

    window.addEventListener('orientationchange', onViewportChange);
    window.addEventListener('resize', onViewportChange);
  });
})();