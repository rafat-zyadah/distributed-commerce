
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'reverb',

    key: import.meta.env.VITE_REVERB_APP_KEY,

    wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT || 8080),

    forceTLS: (import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],

    authEndpoint: '/api/broadcasting/auth',

    auth: {
        headers: {
            Accept: 'application/json',
        },
    },

    authorizer: (channel) => ({
        authorize: (socketId, callback) => {
            const token = localStorage.getItem('dc_jwt');

            if (!token) {
                callback(true, new Error('JWT token not found'));
                return;
            }

            fetch('/api/broadcasting/auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'Authorization': `Bearer ${token}`,
                },
                body: JSON.stringify({
                    socket_id: socketId,
                    channel_name: channel.name,
                }),
            })
                .then(async (response) => {
                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(
                            data.message || `Authorization failed: ${response.status}`
                        );
                    }

                    callback(false, data);
                })
                .catch((error) => {
                    console.error('Broadcast authorization failed:', error);
                    callback(true, error);
                });
        },
    }),
});
