/* =========================
   i-QAD Modern Chat Widget
   Plug & Play JS
   Requires: chat_api.php endpoints
   ========================= */

(function () {
  const escapeHtml = (s) => String(s ?? "")
    .replaceAll("&", "&amp;")
    .replaceAll("<", "&lt;")
    .replaceAll(">", "&gt;")
    .replaceAll('"', "&quot;")
    .replaceAll("'", "&#039;");

  const fmtTime = (iso) => {
    const s = String(iso || "");
    if (!s) return "";
    // supports "YYYY-MM-DD HH:MM:SS" or ISO
    const d = new Date(s.replace(" ", "T"));
    if (isNaN(d.getTime())) return s.slice(0, 16);

    const now = new Date();
    const sameDay =
      d.getFullYear() === now.getFullYear() &&
      d.getMonth() === now.getMonth() &&
      d.getDate() === now.getDate();

    if (sameDay) {
      return d.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
    }
    return d.toLocaleDateString([], { year: "numeric", month: "short", day: "2-digit" });
  };

  function createWidgetHtml() {
    const wrap = document.createElement("div");
    wrap.className = "iqad-chat";
    wrap.innerHTML = `
      <button class="iqad-chat-fab" type="button" title="Messaging">
        <span class="iqad-chat-badge">0</span>
        <!-- icon: simple bubble -->
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <path d="M4 5.5C4 4.12 5.12 3 6.5 3h11C18.88 3 20 4.12 20 5.5v7C20 13.88 18.88 15 17.5 15H10l-4.2 3.1c-.6.44-1.8.06-1.8-.86V5.5Z" stroke="white" stroke-width="2" stroke-linejoin="round"/>
          <path d="M7 7.8h10M7 10.8h7" stroke="white" stroke-width="2" stroke-linecap="round"/>
        </svg>
      </button>

      <div class="iqad-chat-panel hidden" role="dialog" aria-label="Messaging">
        <div class="iqad-chat-top">
          <div class="iqad-chat-title">
            <span class="iqad-chat-dot"></span>
            Messaging
          </div>
          <div class="iqad-chat-actions">
            <button class="iqad-chat-iconbtn" data-act="refresh" title="Refresh">⟲</button>
            <button class="iqad-chat-iconbtn" data-act="close" title="Close">✕</button>
          </div>
        </div>

        <div class="iqad-chat-body">
          <div class="iqad-chat-left">
            <div class="iqad-chat-tabs">
              <button class="iqad-chat-tab active" data-tab="chats">Chats</button>
              <button class="iqad-chat-tab" data-tab="users">Users</button>
            </div>
            <div class="iqad-chat-search">
              <input type="text" placeholder="Search..." data-el="search">
            </div>
            <div class="iqad-chat-list" data-el="list"></div>
          </div>

          <div class="iqad-chat-right">
            <div class="iqad-chat-peerbar" data-el="peerbar">
              <div class="iqad-chat-peer">
                Select a user
                <small>Pick from Users or your Chats</small>
              </div>
            </div>

            <div class="iqad-chat-messages" data-el="messages">
              <div class="iqad-chat-empty">👋 Select a user to start chatting.</div>
            </div>

            <div class="iqad-chat-compose">
              <input type="text" placeholder="Type a message..." data-el="input">
              <button type="button" data-act="send">Send</button>
            </div>
          </div>
        </div>
      </div>
    `;
    return wrap;
  }

  function ChatWidget(opts) {
    const apiUrl = opts.apiUrl || "chat_api.php";
    const me = opts.me || { id: 0 };
    const pollMs = Number(opts.pollMs || 3500);

    let open = false;
    let mode = "chats"; // chats | users
    let users = [];
    let chats = [];
    let filtered = [];
    let peerId = 0;
    let peer = {};
    let pollTimer = null;

    const root = createWidgetHtml();
    const fab = root.querySelector(".iqad-chat-fab");
    const badge = root.querySelector(".iqad-chat-badge");
    const panel = root.querySelector(".iqad-chat-panel");

    const listEl = root.querySelector('[data-el="list"]');
    const searchEl = root.querySelector('[data-el="search"]');
    const messagesEl = root.querySelector('[data-el="messages"]');
    const peerbarEl = root.querySelector('[data-el="peerbar"]');
    const inputEl = root.querySelector('[data-el="input"]');
    const sendBtn = root.querySelector('[data-act="send"]');

    const tabChats = root.querySelector('[data-tab="chats"]');
    const tabUsers = root.querySelector('[data-tab="users"]');

    const apiGet = async (path) => {
      const res = await fetch(`${apiUrl}?${path}`, { credentials: "same-origin" });
      return await res.json();
    };

    const apiPost = async (action, body) => {
      const res = await fetch(`${apiUrl}?action=${encodeURIComponent(action)}`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        credentials: "same-origin",
        body: JSON.stringify(body || {})
      });
      return await res.json();
    };

    const setBadge = (n) => {
      const c = Number(n || 0);
      badge.textContent = c;
      badge.style.display = c > 0 ? "block" : "none";
    };

    const updateUnreadBadge = async () => {
      try {
        const d = await apiGet("action=get_unread_messages_count");
        if (d && d.success) setBadge(d.count);
      } catch {}
    };

    const loadUsers = async () => {
      const d = await apiGet("action=get_chat_users");
      users = (d.users || []);
    };

    const loadChats = async () => {
      const d = await apiGet("action=get_conversations");
      chats = (d.items || []);
    };

    const markRead = async (pid) => {
      try { await apiPost("mark_read", { peer_id: Number(pid) }); } catch {}
    };

    const renderList = (useFiltered = false) => {
      let items = [];
      if (useFiltered) items = filtered || [];
      else {
        items = mode === "users" ? (users || []) : (chats || []);
        filtered = items;
      }

      if (!items.length) {
        listEl.innerHTML = `<div class="iqad-chat-empty">No results</div>`;
        return;
      }

      if (mode === "users") {
        listEl.innerHTML = items.map(u => {
          const active = peerId === Number(u.id) ? "active" : "";
          return `
            <div class="iqad-chat-item ${active}" data-peer="${Number(u.id)}"
                 data-name="${escapeHtml(u.name)}"
                 data-role="${escapeHtml(u.role)}"
                 data-sdo="${escapeHtml(u.sdo || "Unassigned")}">
              <b>${escapeHtml(u.name)}</b>
              <small>${escapeHtml(String(u.role || "").toUpperCase())}${u.sdo ? " • " + escapeHtml(u.sdo) : ""}</small>
            </div>
          `;
        }).join("");
      } else {
        listEl.innerHTML = items.map(c => {
          const active = peerId === Number(c.peer_id) ? "active" : "";
          const unread = Number(c.unread || 0);
          const last = escapeHtml(c.last_message || "");
          return `
            <div class="iqad-chat-item ${active}" data-peer="${Number(c.peer_id)}"
                 data-name="${escapeHtml(c.peer_name)}"
                 data-role="${escapeHtml(c.peer_role)}"
                 data-sdo="${escapeHtml(c.peer_sdo || "Unassigned")}">
              <b>
                <span style="min-width:0; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                  ${escapeHtml(c.peer_name)}
                </span>
                <span style="color:var(--iqad-muted); font-weight:900; font-size:.72rem">
                  ${fmtTime(c.updated_at)}
                </span>
              </b>
              <small>${escapeHtml(String(c.peer_role || "").toUpperCase())}${c.peer_sdo ? " • " + escapeHtml(c.peer_sdo) : ""}</small>
              <small class="iqad-chat-last">${last}</small>
              ${unread > 0 ? `<span class="iqad-chat-unread">${unread} new</span>` : ``}
            </div>
          `;
        }).join("");
      }
    };

    const applySearch = (term) => {
      const t = (term || "").toLowerCase().trim();
      if (!t) { renderList(false); return; }

      if (mode === "users") {
        filtered = users.filter(u =>
          (u.name || "").toLowerCase().includes(t) ||
          (u.role || "").toLowerCase().includes(t) ||
          (u.sdo  || "").toLowerCase().includes(t)
        );
      } else {
        filtered = chats.filter(c =>
          (c.peer_name || "").toLowerCase().includes(t) ||
          (c.peer_role || "").toLowerCase().includes(t) ||
          (c.peer_sdo  || "").toLowerCase().includes(t) ||
          (c.last_message || "").toLowerCase().includes(t)
        );
      }
      renderList(true);
    };

    const renderPeerBar = () => {
      if (!peerId) {
        peerbarEl.innerHTML = `
          <div class="iqad-chat-peer">
            Select a user
            <small>Pick from Users or your Chats</small>
          </div>
        `;
        return;
      }

      peerbarEl.innerHTML = `
        <div class="iqad-chat-peer">
          ${escapeHtml(peer.name)}
          <small>${escapeHtml(String(peer.role || "").toUpperCase())}${peer.sdo ? " • " + escapeHtml(peer.sdo) : ""}</small>
        </div>
        <div style="display:flex; gap:8px; align-items:center">
          <button class="iqad-chat-iconbtn" data-act="reload-thread" title="Reload">⟲</button>
        </div>
      `;
    };

    const loadMessages = async (pid, silent = false) => {
      if (!pid) return;

      const d = await apiGet(`action=get_messages&peer_id=${encodeURIComponent(pid)}`);
      const rows = d.messages || [];

      if (!rows.length) {
        messagesEl.innerHTML = `<div class="iqad-chat-empty">No messages yet. Say hello 👋</div>`;
        return;
      }

      messagesEl.innerHTML = rows.map(m => {
        const isMe = Number(m.from_id) === Number(me.id);
        const t = fmtTime(m.created_at);
        return `
          <div class="iqad-msg-row ${isMe ? "me" : ""}">
            <div class="iqad-bubble">
              ${escapeHtml(m.body || "")}
              <div class="iqad-meta">${t}</div>
            </div>
          </div>
        `;
      }).join("");

      // Scroll to bottom
      messagesEl.scrollTop = messagesEl.scrollHeight;
      if (!silent) setTimeout(() => { messagesEl.scrollTop = messagesEl.scrollHeight; }, 50);
    };

    const openPeer = async (id, name, role, sdo) => {
      peerId = Number(id);
      peer = { id: peerId, name, role, sdo };

      renderPeerBar();
      await loadMessages(peerId);
      await markRead(peerId);
      await updateUnreadBadge();
      await loadChats();
      renderList(false);
    };

    const setMode = async (m) => {
      mode = m;
      tabChats.classList.toggle("active", mode === "chats");
      tabUsers.classList.toggle("active", mode === "users");
      searchEl.value = "";
      renderList(false);
    };

    const refresh = async () => {
      listEl.innerHTML = `<div class="iqad-chat-empty">Loading...</div>`;
      await Promise.all([loadUsers(), loadChats(), updateUnreadBadge()]);
      renderList(false);
    };

    const startPolling = () => {
      stopPolling();
      pollTimer = setInterval(async () => {
        if (!open) return;
        await updateUnreadBadge();
        await loadChats();
        renderList(searchEl.value.trim() ? true : false);

        if (peerId) {
          await loadMessages(peerId, true);
          await markRead(peerId);
        }
      }, pollMs);
    };

    const stopPolling = () => {
      if (pollTimer) clearInterval(pollTimer);
      pollTimer = null;
    };

    const toggle = async () => {
      open = !open;
      panel.classList.toggle("hidden", !open);

      if (open) {
        await refresh();
        startPolling();
        inputEl.focus();
      } else {
        stopPolling();
      }
    };

    const send = async () => {
      const text = (inputEl.value || "").trim();
      if (!peerId) return;
      if (!text) return;

      sendBtn.disabled = true;
      inputEl.value = "";

      // Optimistic bubble
      const temp = document.createElement("div");
      temp.className = "iqad-msg-row me";
      temp.innerHTML = `
        <div class="iqad-bubble">
          ${escapeHtml(text)}
          <div class="iqad-meta">Sending...</div>
        </div>
      `;
      messagesEl.appendChild(temp);
      messagesEl.scrollTop = messagesEl.scrollHeight;

      try {
        const d = await apiPost("send_message", { to_id: peerId, body: text });
        if (!d.success) throw new Error(d.message || "Failed");
        await loadMessages(peerId, true);
        await loadChats();
        renderList(searchEl.value.trim() ? true : false);
        await updateUnreadBadge();
      } catch (e) {
        temp.querySelector(".iqad-meta").textContent = "Failed to send";
      } finally {
        sendBtn.disabled = false;
        inputEl.focus();
      }
    };

    // Events
    fab.addEventListener("click", toggle);

    root.querySelector('[data-act="close"]').addEventListener("click", toggle);
    root.querySelector('[data-act="refresh"]').addEventListener("click", refresh);

    tabChats.addEventListener("click", () => setMode("chats"));
    tabUsers.addEventListener("click", () => setMode("users"));

    searchEl.addEventListener("input", () => applySearch(searchEl.value));

    listEl.addEventListener("click", async (e) => {
      const item = e.target.closest(".iqad-chat-item");
      if (!item) return;
      await openPeer(
        item.getAttribute("data-peer"),
        item.getAttribute("data-name"),
        item.getAttribute("data-role"),
        item.getAttribute("data-sdo")
      );
    });

    peerbarEl.addEventListener("click", async (e) => {
      const btn = e.target.closest('[data-act="reload-thread"]');
      if (!btn || !peerId) return;
      await loadMessages(peerId);
      await markRead(peerId);
      await updateUnreadBadge();
    });

    inputEl.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        send();
      }
    });
    sendBtn.addEventListener("click", send);

    // Public API
    return {
      mount(container) {
        (container || document.body).appendChild(root);
        // badge poll even if closed
        updateUnreadBadge();
        setInterval(updateUnreadBadge, 4000);
      }
    };
  }

  // Global init helper (Plug & Play)
  window.IQADChat = {
    init(options) {
      const cfg = options || {};
      const widget = ChatWidget(cfg);
      widget.mount(cfg.container || document.body);
      return widget;
    }
  };
})();
