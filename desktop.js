// Stellt sicher, dass das gesamte HTML geladen ist, bevor das Skript startet.
$(function() {

    // Diese Funktion macht ein Fenster zieh- und größenveränderbar.
    function makeWindowInteractive(windowElement) {
        windowElement.draggable({
            handle: ".window-header",      // Man kann das Fenster nur an der Kopfleiste ziehen
            containment: "#desktop",       // Das Fenster kann den Desktop nicht verlassen
            scroll: false
        }).resizable({
            handles: "n, e, s, w, ne, se, sw, nw", // Anfasser an allen Ecken und Seiten
            minHeight: 250,
            minWidth: 400
        });
    }

    // Diese Funktion öffnet ein Fenster, wenn auf ein Icon geklickt wird.
    function openWindow(iconId) {
        // Prüfen, ob ein Fenster mit dieser ID schon existiert.
        const existingWindow = $(`.window-container[data-window-id="${iconId}"]`);
        
        if (existingWindow.length > 0) {
            // Wenn das Fenster nur minimiert (versteckt) ist, zeige es wieder an.
            existingWindow.show();
            // Bringe es in den Vordergrund.
            existingWindow.trigger('mousedown');
            return; // Beende die Funktion hier.
        }

        // Wenn das Fenster noch nicht existiert, erstelle es aus der Vorlage.
        let windowTemplateHtml;
        if (iconId === 'console') {
            windowTemplateHtml = $('#console-window-template').html();
        } else if (iconId === 'emails') {
            windowTemplateHtml = $('#email-window-template').html();
        } else {
            return; // Beende, wenn die Icon-ID unbekannt ist.
        }

        const newWindow = $(windowTemplateHtml);
        $('#desktop').append(newWindow); // Füge das neue Fenster zum Desktop hinzu.
        makeWindowInteractive(newWindow); // Mache das neue Fenster interaktiv.
        newWindow.trigger('mousedown');   // Bringe das neue Fenster sofort in den Vordergrund.
    }

    // --- Event Listeners ---

    // Event Listener für den Doppelklick auf die Desktop-Icons.
    $('#icon-console').on('dblclick', function() {
        openWindow('console');
    });

    $('#icon-emails').on('dblclick', function() {
        openWindow('emails');
    });

    // Event Listener für die Fenster-Buttons (Schließen, Minimieren).
    // Wichtig: .on() benutzen, da die Fenster dynamisch zum #desktop hinzugefügt werden.
    $('#desktop').on('click', '.win-close', function() {
        $(this).closest('.window-container').remove(); // Fenster komplett entfernen
    });

    $('#desktop').on('click', '.win-minimize', function() {
        $(this).closest('.window-container').hide(); // Fenster nur verstecken
    });

    // Bringt das angeklickte Fenster per Klick auf die Kopfleiste in den Vordergrund.
    $('#desktop').on('mousedown', '.window-container', function() {
        // Setze zuerst alle Fenster auf einen niedrigeren z-index.
        $('.window-container').css('z-index', 10);
        // Setze dann das angeklickte Fenster auf einen höheren z-index.
        $(this).css('z-index', 11);
    });

});