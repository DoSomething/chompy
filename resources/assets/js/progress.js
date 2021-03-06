/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// Listen to the 'LogProgress Event' event on the importer channel.
window.Echo.channel('importer')
    .listen('LogProgress', (e) => {
        const messageType = e.type;
        const messageContent = e.message;
        const value = e.progressValue;

        // @TODO - don't use jquery
        if (messageType == 'general') {
            $('#messages').append('<code>' + e.message + '</code>\n');
        }

        if (messageType == 'progress') {
            $('.progress-bar').attr("aria-valuenow", value);
            $('.progress-bar').attr('style', 'width: ' + value + '%');
        }
    });