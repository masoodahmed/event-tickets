( function ( $ ) {
    // Localized data is required to run this script.
    if ( window.TECFtEditorData === undefined || window.TECFtEditorData.seriesRelationship === undefined || window.TECFtEditorData.classic === undefined ) {
        return;
    }

    const {
        ticketPanelEditSelector, ticketPanelEditDefaultProviderAttribute, ticketsMetaboxSelector,
    } = window.TECFtEditorData.classic;

    // Get the current file name and its minified status.
    const file = ( new Error ).stack.split ( '/' ).slice ( -1 ).join ();
    const min = file.includes ( '.min.js' ) ? '.min' : '';

    /**
     * Subscribe to Series relationship and ticket provider changes to lock/unlock the post publish button and
     * show/hide the notice.
     */
    async function onReady() {
        const {
            subscribeToSeriesChange,
            getSeriesProviderFromEvent,
            removeDiscordantProviderNotice,
            showDiscordantProviderNotice,
            getSeriesTitleFromEvent,
            getSeriesProviderFromSelection,
            getSeriesTitleFromSelection
        } = await import(`./modules/series-relationship${ min }.js`);

        const ticketsMetabox = $ ( ticketsMetaboxSelector );

        /**
         * Get the event ticket provider from the ticket panel attribute.
         *
         * @returns {string} The event ticket provider.
         */
        function getEventProviderFromPanel() {
            return document.getElementById ( ticketPanelEditSelector.substring ( 1 ) )
                .getAttribute ( ticketPanelEditDefaultProviderAttribute );
        }

        /**
         * Get the event title from the post title input.
         *
         * @returns {string} The event title.
         */
        function getEventTitle() {
            return document.getElementById ( 'title' ).value;
        }

        /**
         * Lock the post publish  and "Save Draft" buttons.
         */
        function lockPostPublish() {
            Array.from ( document.querySelectorAll ( '#publish,#save-post' ) ).forEach ( el => el.disabled = true );
        }

        /**
         * Unlock the post publish and "Save Draft" buttons.
         */
        function unlockPostPublish() {
            Array.from ( document.querySelectorAll ( '#publish,#save-post' ) ).forEach ( el => el.disabled = false );
        }

        /**
         * Toggle the publish lock based on the event and series providers.
         *
         * @param {string|null} eventProvider The current event ticket provider.
         * @param {string|null} seriesProvider The current series ticket provider.
         * @param {string} seriesTitle Thte title of the currently selected series.
         */
        function togglePublishLock( eventProvider, seriesProvider, seriesTitle ) {
            if ( eventProvider === seriesProvider || eventProvider === null || seriesProvider === null ) {
                unlockPostPublish ();
                removeDiscordantProviderNotice ();

                return;
            }

            lockPostPublish ();
            showDiscordantProviderNotice ( getEventTitle (), seriesTitle );
        }

        /**
         * Toggle the publish lock when the event ticket provider is changed in the ticket panel.
         */
        function onTicketProviderChange() {
            const seriesProvider = getSeriesProviderFromSelection ();
            const eventProvider = getEventProviderFromPanel ();
            const seriesTitle = getSeriesTitleFromSelection ();
            togglePublishLock ( eventProvider, seriesProvider, seriesTitle );

        }

        /**
         * Toggle the publish lock when the series is changed in the metabox dropdown.
         *
         * @param {Event} event The 'change' event dispatched by Select2.
         */
        function onSeriesChange( event ) {
            const seriesProvider = getSeriesProviderFromEvent ( event );
            const eventProvider = getEventProviderFromPanel ();
            const seriesTitle = getSeriesTitleFromEvent ( event );
            togglePublishLock ( eventProvider, seriesProvider, seriesTitle );
        }

        /**
         * Subscribe to the event dispatched after any ticket panel is swapped.
         *
         * @param {function} onChange The callback function to be called when the ticket panel is swapped.
         */
        function subscribeToTicketProviderChange( onChange ) {
            ticketsMetabox.on ( 'after_panel_swap.tickets', onChange );
        }

        subscribeToSeriesChange ( onSeriesChange );
        subscribeToTicketProviderChange ( onTicketProviderChange );
    }

    // On ready import the module that contains the common functions and init.
    if ( document.readyState !== 'loading' ) {
        onReady ();
    } else {
        document.addEventListener ( 'DOMContentLoaded', onReady );
    }
} ) ( jQuery );