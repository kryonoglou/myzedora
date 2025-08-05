<div x-data="updateChecker()" x-init="checkVersion()" class="space-y-6">
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

        <div x-show="!loading && !error" style="display: none;">
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
                    <a :href="downloadUrl" target="_blank" class="inline-block bg-sky-500 text-white font-semibold px-8 py-3 rounded-lg hover:bg-sky-600 transition-colors duration-300">
                        <?php echo htmlspecialchars($settings_data['status_download_button']); ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function updateChecker() {
        return {
            loading: true,
            error: '',
            currentVersion: '<?php echo MYZEDORA_VERSION; ?>',
            latestVersion: 'N/A',
            downloadUrl: '#',
            updateAvailable: false,
            checkVersion() {
                fetch('<?php echo $base_url . "/includes/fetch_update.php"; ?>')
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }
                        this.latestVersion = data.latestVersion;
                        this.downloadUrl = data.downloadUrl;
                        this.updateAvailable = data.updateAvailable;
                    })
                    .catch(error => {
                        this.error = '<?php echo htmlspecialchars($settings_data['status_error']); ?>';
                    })
                    .finally(() => {
                        this.loading = false;
                    });
            }
        }
    }
</script>