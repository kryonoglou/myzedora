<div x-data="updateController()" x-init="checkVersion()" class="space-y-6">
    <div class="p-8 bg-gray-900/50 rounded-lg text-center">
        <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($settings_data['status_title']); ?></h3>
        <p class="text-gray-400"><?php echo htmlspecialchars($settings_data['status_subtitle']); ?></p>
    </div>

    <div class="p-8 bg-gray-900/50 rounded-lg">
        <div x-show="loading" class="text-center text-gray-400">
            <p><?php echo htmlspecialchars($settings_data['status_checking']); ?></p>
        </div>

        <div x-show="error" class="bg-red-500/20 text-red-300 p-4 rounded-lg text-center" style="display: none;">
            <p x-text="error"></p>
        </div>

        <div x-show="!loading && !error && !isUpdating" style="display: none;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-center">
                <div>
                    <p class="text-gray-400 text-sm font-semibold uppercase"><?php echo htmlspecialchars($settings_data['status_current_version']); ?></p>
                    <p class="text-3xl font-bold text-white mt-1" x-text="currentVersion"></p>
                </div>
                <div>
                    <p class="text-gray-400 text-sm font-semibold uppercase"><?php echo htmlspecialchars($settings_data['status_latest_version']); ?></p>
                    <p class="text-3xl font-bold mt-1" :class="updateAvailable ? 'text-green-400' : 'text-white'" x-text="latestVersion"></p>
                </div>
            </div>

            <div class="mt-8 text-center">
                <div x-show="!updateAvailable">
                    <p class="text-green-400 font-semibold"><?php echo htmlspecialchars($settings_data['status_up_to_date']); ?></p>
                </div>
                <div x-show="updateAvailable" style="display: none;">
                    <p class="text-yellow-400 font-semibold mb-4"><?php echo htmlspecialchars($settings_data['status_update_available']); ?></p>
                    <button @click="performUpdate()" class="inline-block bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        Update Now
                    </button>
                </div>
            </div>
        </div>

        <div x-show="isUpdating" class="text-center" style="display: none;">
             <div class="flex justify-center items-center mb-4">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-lg text-yellow-400 font-semibold">Update in progress...</p>
            </div>
            <p class="text-gray-400">Please do not close this window. This may take a few minutes.</p>
            <p class="text-gray-500 mt-2 text-sm" x-text="updateMessage"></p>
        </div>
        
        <div x-show="updateStatus" class="mt-6 p-4 rounded-lg text-center" :class="{ 'bg-green-500/20 text-green-300': updateStatus === 'success', 'bg-red-500/20 text-red-300': updateStatus === 'error' }" style="display: none;">
            <p x-text="updateMessage"></p>
        </div>

    </div>
</div>

<script>
    function updateController() {
        return {
            loading: true,
            error: '',
            currentVersion: '<?php echo MYZEDORA_VERSION; ?>',
            latestVersion: 'N/A',
            updateAvailable: false,

            isUpdating: false,
            updateMessage: '',
            updateStatus: '',

            checkVersion() {
                this.loading = true;
                this.error = '';
                fetch('<?php echo $base_url . "/includes/fetch_update.php"; ?>')
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok.');
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) throw new Error(data.error);
                        this.latestVersion = data.latestVersion;
                        this.updateAvailable = data.updateAvailable;
                    })
                    .catch(error => {
                        this.error = '<?php echo htmlspecialchars(addslashes($settings_data['status_error'])); ?>: ' + error.message;
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            },

            performUpdate() {
                this.isUpdating = true;
                this.updateMessage = 'Initializing update...';
                this.updateStatus = '';

                fetch('<?php echo $base_url . "/includes/update.php"; ?>', {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    this.updateStatus = data.status;
                    this.updateMessage = data.message;
                    if(data.status === 'success') {
                        this.updateAvailable = false; 
                        setTimeout(() => window.location.reload(), 4000);
                    }
                })
                .catch(error => {
                    this.updateStatus = 'error';
                    this.updateMessage = 'A critical error occurred during the update process. Please check server logs or try a manual update.';
                })
                .finally(() => {
                    this.isUpdating = false;
                });
            }
        }
    }
</script>