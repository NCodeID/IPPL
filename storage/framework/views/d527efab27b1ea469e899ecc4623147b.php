<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
        <title>RBAC Demo</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100">
        <div class="min-h-screen py-8">
            <div class="max-w-4xl mx-auto px-4">
                <h1 class="text-3xl font-bold text-center mb-8">RBAC Demo</h1>

                <!-- Login Form -->
                <div id="login-section" class="bg-white rounded-lg shadow p-8 mb-8">
                    <h2 class="text-xl font-semibold mb-4">Login</h2>
                    <form id="login-form">
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input type="email" id="email" required class="w-full px-3 py-2 border rounded">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-1">Password</label>
                            <input type="password" id="password" required class="w-full px-3 py-2 border rounded">
                        </div>
                        <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                            Login
                        </button>
                    </form>
                    <p class="mt-4 text-sm text-gray-600">
                        Demo: admin@pos.com / password, kasir@pos.com / password
                    </p>
                </div>

                <!-- RBAC Dashboard -->
                <div id="dashboard" class="hidden">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-semibold">Dashboard</h2>
                        <button id="logout-btn" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            Logout
                        </button>
                    </div>

                    <!-- User Info -->
                    <div class="bg-white rounded-lg shadow p-6 mb-6">
                        <h3 class="font-semibold mb-3">User Info</h3>
                        <button id="load-user" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 mb-3">
                            Load Profile
                        </button>
                        <div id="user-detail"></div>
                    </div>

                    <!-- RBAC Endpoints -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="font-semibold mb-3">Protected Endpoints</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <button data-url="/api/roles" class="endpoint-btn bg-purple-600 text-white py-2 rounded hover:bg-purple-700">
                                GET /api/roles (needs manage_roles)
                            </button>
                            <button data-url="/api/permissions" class="endpoint-btn bg-purple-600 text-white py-2 rounded hover:bg-purple-700">
                                GET /api/permissions (needs manage_permissions)
                            </button>
                        </div>
                        <p class="mt-4 text-sm text-gray-600">
                            Admin has all permissions. Kasir has manage_orders, manage_payments, manage_cash_transactions only.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <script>
            const token = localStorage.getItem('auth_token');
            if (token) {
                document.getElementById('login-section').classList.add('hidden');
                document.getElementById('dashboard').classList.remove('hidden');
            }

            // Login
            document.getElementById('login-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const res = await fetch('/api/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        email: document.getElementById('email').value,
                        password: document.getElementById('password').value,
                    }),
                });
                const data = await res.json();
                if (data.token) {
                    localStorage.setItem('auth_token', data.token);
                    document.getElementById('login-section').classList.add('hidden');
                    document.getElementById('dashboard').classList.remove('hidden');
                } else {
                    alert(data.message || 'Login failed');
                }
            });

            // Logout
            document.getElementById('logout-btn').addEventListener('click', async () => {
                const token = localStorage.getItem('auth_token');
                await fetch('/api/logout', {
                    method: 'POST',
                    headers: { 'Authorization': 'Bearer ' + token },
                });
                localStorage.removeItem('auth_token');
                document.getElementById('login-section').classList.remove('hidden');
                document.getElementById('dashboard').classList.add('hidden');
            });

            // Load user
            document.getElementById('load-user').addEventListener('click', async () => {
                const token = localStorage.getItem('auth_token');
                const res = await fetch('/api/user', {
                    headers: { 'Authorization': 'Bearer ' + token },
                });
                const data = await res.json();
                const perms = data.role?.permissions?.map(p => p.name) || [];
                document.getElementById('user-detail').innerHTML =
                    '<p><strong>Name:</strong> ' + data.name + '</p>' +
                    '<p><strong>Email:</strong> ' + data.email + '</p>' +
                    '<p><strong>Role:</strong> ' + (data.role?.name || 'N/A') + '</p>' +
                    '<p class="mb-2"><strong>Permissions:</strong></p>' +
                    perms.map(p => '<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded mr-1 mb-1">' + p + '</span>').join('');
            });

            // Protected endpoints
            document.querySelectorAll('.endpoint-btn').forEach(btn => {
                btn.addEventListener('click', async () => {
                    const token = localStorage.getItem('auth_token');
                    const url = btn.dataset.url;
                    const res = await fetch(url, {
                        headers: { 'Authorization': 'Bearer ' + token },
                    });
                    const data = await res.json();
                    if (Array.isArray(data.data)) {
                        alert('SUCCESS: Loaded ' + data.data.length + ' items');
                    } else {
                        alert('FORBIDDEN: ' + (data.message || 'Access denied') + ' (status ' + res.status + ')');
                    }
                });
            });
        </script>
    </body>
</html>
<?php /**PATH /home/ncodeid/Documents/IMPAL/be2/resources/views/rbac.blade.php ENDPATH**/ ?>