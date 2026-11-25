(() => {
    const trigger = document.getElementById("accessibility-trigger");
    const panel = document.getElementById("accessibility-panel");
    if (!trigger || !panel) return;

    const decrease = document.getElementById("accessibility-decrease");
    const increase = document.getElementById("accessibility-increase");
    const sizeLabel = document.getElementById("accessibility-size");
    const contrastButton = document.getElementById("accessibility-contrast");
    const spacingButton = document.getElementById("accessibility-spacing");
    const linksButton = document.getElementById("accessibility-links");
    const resetButton = document.getElementById("accessibility-reset");
    const closeButton = document.getElementById("accessibility-close");
    const status = document.getElementById("accessibility-status");
    const storageKey = "dryve_acessibilidade";
    const defaultSettings = { size: 16, contrast: false, spacing: false, links: false };
    const minSize = 14;
    const maxSize = 32;
    let settings = { ...defaultSettings };

    try {
        const saved = JSON.parse(localStorage.getItem(storageKey));
        if (saved && typeof saved === "object") {
            settings.size = Number.isInteger(saved.size) && saved.size >= minSize && saved.size <= maxSize ? saved.size : 16;
            settings.contrast = saved.contrast === true;
            settings.spacing = saved.spacing === true;
            settings.links = saved.links === true;
        }
    } catch (error) {
        settings = { ...defaultSettings };
    }

    function announce(message) {
        status.textContent = message;
    }

    function saveSettings() {
        try {
            localStorage.setItem(storageKey, JSON.stringify(settings));
        } catch (error) {
            announce("O navegador bloqueou o salvamento das preferências.");
        }
    }

    function updateToggle(button, active) {
        button.setAttribute("aria-pressed", String(active));
        button.querySelector("span").textContent = active ? "✓" : "○";
    }

    function applySettings() {
        const root = document.documentElement;
        const scale = settings.size / defaultSettings.size;
        root.style.fontSize = scale * 100 + "%";
        root.style.setProperty("--escala-texto", scale);
        root.classList.toggle("high-contrast", settings.contrast);
        root.classList.toggle("extra-spacing", settings.spacing);
        root.classList.toggle("highlight-links", settings.links);
        sizeLabel.textContent = Math.round(scale * 100) + "%";
        decrease.disabled = settings.size <= minSize;
        increase.disabled = settings.size >= maxSize;
        updateToggle(contrastButton, settings.contrast);
        updateToggle(spacingButton, settings.spacing);
        updateToggle(linksButton, settings.links);
    }

    function changeSetting(key, value, message) {
        settings[key] = value;
        applySettings();
        saveSettings();
        if (message) announce(message);
    }

    function setPanelOpen(open) {
        panel.hidden = !open;
        trigger.setAttribute("aria-expanded", String(open));
        trigger.setAttribute("aria-label", open ? "Fechar opções de acessibilidade" : "Abrir opções de acessibilidade");
        if (open) {
            closeButton.focus();
        } else {
            trigger.focus();
        }
    }

    trigger.addEventListener("click", () => setPanelOpen(panel.hidden));
    closeButton.addEventListener("click", () => setPanelOpen(false));

    decrease.addEventListener("click", () => {
        changeSetting("size", Math.max(minSize, settings.size - 2));
        if (decrease.disabled) increase.focus();
    });
    increase.addEventListener("click", () => {
        changeSetting("size", Math.min(maxSize, settings.size + 2));
        if (increase.disabled) decrease.focus();
    });
    contrastButton.addEventListener("click", () => {
        changeSetting("contrast", !settings.contrast);
    });
    spacingButton.addEventListener("click", () => {
        changeSetting("spacing", !settings.spacing);
    });
    linksButton.addEventListener("click", () => {
        changeSetting("links", !settings.links);
    });
    resetButton.addEventListener("click", () => {
        settings = { ...defaultSettings };
        applySettings();
        saveSettings();
        announce("Preferências restauradas.");
    });
    document.addEventListener("keydown", event => {
        if (event.key === "Escape" && !panel.hidden) setPanelOpen(false);
    });
    document.addEventListener("pointerdown", event => {
        if (!panel.hidden && !event.target.closest(".accessibility-widget")) {
            panel.hidden = true;
            trigger.setAttribute("aria-expanded", "false");
            trigger.setAttribute("aria-label", "Abrir opções de acessibilidade");
        }
    });

    applySettings();
})();
