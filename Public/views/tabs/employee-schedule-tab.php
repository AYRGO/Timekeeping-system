            <!-- Current Schedule Tab -->
            <div id="current-schedule" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-6">Current Schedule</h2>
                    
                    <!-- Current Schedule Display -->
                    <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                        <h3 class="text-lg font-medium text-blue-800 mb-2">Current Work Schedule</h3>
                        <?php 
                        $currentSched = $employee['official_sched'] ?? null;
                        if ($currentSched && isset($scheduleOptions[$currentSched])): 
                        ?>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="text-center">
                                    <p class="text-sm text-blue-600">Schedule ID</p>
                                    <p class="text-2xl font-bold text-blue-800">#<?= $currentSched ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-blue-600">Time In</p>
                                    <p class="text-2xl font-bold text-blue-800"><?= $scheduleOptions[$currentSched]['in'] ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-sm text-blue-600">Time Out</p>
                                    <p class="text-2xl font-bold text-blue-800"><?= $scheduleOptions[$currentSched]['out'] ?></p>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <p class="text-gray-500">No schedule assigned</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Change Schedule Form -->
                    <form method="post" class="space-y-6">
                        <input type="hidden" name="update_schedule" value="1">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select New Schedule</label>
                            <select name="official_sched" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Select a schedule...</option>
                                <?php foreach ($scheduleOptions as $id => $times): ?>
                                    <option value="<?= $id ?>" <?= $currentSched == $id ? 'selected' : '' ?>>
                                        Schedule <?= $id ?> - <?= $times['in'] ?> to <?= $times['out'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Schedule Options Preview -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($scheduleOptions as $id => $times): ?>
                                <div class="border border-gray-200 rounded-lg p-4 <?= $currentSched == $id ? 'bg-blue-50 border-blue-300' : 'hover:bg-gray-50' ?>">
                                    <div class="text-center">
                                        <h4 class="font-semibold text-gray-800">Schedule <?= $id ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?= $times['in'] ?> - <?= $times['out'] ?></p>
                                        <?php if ($currentSched == $id): ?>
                                            <span class="inline-block mt-2 px-2 py-1 bg-blue-100 text-blue-800 text-xs rounded-full">Current</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-md transition">
                                <i class="fas fa-save mr-2"></i>Update Schedule
                            </button>
                        </div>
                    </form>
                </div>
            </div>