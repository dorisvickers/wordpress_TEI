document.addEventListener("DOMContentLoaded", function () {
    const popup = document.createElement("div");
    popup.className = "tei-popup";
    document.body.appendChild(popup);

    const toggleRefs = document.getElementById("toggle-refs");
    const toggleNotes = document.getElementById("toggle-notes");
    const toggleLines = document.getElementById("toggle-lines");

    function updateButtonLabel(btn) {
        const isOff = btn.classList.contains("off");
        btn.textContent = isOff ? btn.dataset.labelOff : btn.dataset.labelOn;
    }

    function updateVisibility() {
        const showRefs = !toggleRefs.classList.contains("off");
        const showNotes = !toggleNotes.classList.contains("off");
        const showLines = !toggleLines.classList.contains("off");

        document.querySelectorAll(".tei-ref").forEach(el => {
            if (showRefs) {
                el.classList.remove("no-gloss");
                if (el.dataset.tooltipOriginal) {
                    el.dataset.tooltip = el.dataset.tooltipOriginal;
                }
            } else {
                el.classList.add("no-gloss");
                if (!el.dataset.tooltipOriginal) {
                    el.dataset.tooltipOriginal = el.dataset.tooltip;
                }
                el.dataset.tooltip = "";
            }
        });

        document.querySelectorAll(".tei-note").forEach(el => {
            el.style.display = showNotes ? "inline" : "none";
        });

        document.querySelectorAll(".tei-line-num").forEach(el => {
            el.style.display = showLines ? "inline-block" : "none";
        });
    }

    [toggleRefs, toggleNotes, toggleLines].forEach(btn => {
        btn.addEventListener("click", () => {
            btn.classList.toggle("off");
            updateButtonLabel(btn);
            updateVisibility();
        });
        updateButtonLabel(btn);
    });

    updateVisibility();

    document.body.addEventListener("mouseover", e => {
        const ref = e.target.closest(".tei-ref, .tei-note");
        if (!ref || ref.classList.contains("no-gloss")) return;

        const text = ref.dataset.tooltip;
        if (text) {
            popup.textContent = text;
            popup.style.display = "block";
        }
    });

    document.body.addEventListener("mousemove", e => {
        popup.style.top = (e.pageY + 15) + "px";
        popup.style.left = (e.pageX + 15) + "px";
    });

    document.body.addEventListener("mouseout", e => {
        if (e.target.closest(".tei-ref, .tei-note")) {
            popup.style.display = "none";
        }
    });
});
