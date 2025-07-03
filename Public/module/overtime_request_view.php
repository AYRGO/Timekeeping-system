<!-- overtime_request_form.php -->
<div id="overtimeRequestView" class="mt-32">
    <div class="flex justify-center items-center min-h-[60vh] px-4">
        <div class="max-w-xl w-full bg-white p-6 rounded-xl shadow-lg border border-green-200">

            <!-- Confirmation Messages -->
           <?php if (isset($_GET['overtime']) && $_GET['overtime'] === 'success'): ?>
    <!-- success message -->
<?php elseif (isset($_GET['overtime']) && $_GET['overtime'] === 'invalid_time_order'): ?>
    <!-- invalid order message -->
<?php elseif (isset($_GET['overtime']) && $_GET['overtime'] === 'invalid_input'): ?>
    <!-- invalid input message -->
<?php endif; ?>


            <!-- Header -->
            <div class="bg-blue-600 p-4 rounded-lg mb-6 shadow">
                <h2 class="text-2xl font-semibold text-center text-white">Request Overtime</h2>
            </div>

            <!-- Form -->
            <form method="POST" action="time_log_create.php" enctype="multipart/form-data" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="submit_overtime" value="1">

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                    <input type="date" name="overtime_date" value="<?= date('Y-m-d') ?>" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Start Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Start Time</label>
                    <input type="time" name="start_time" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- End Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                    <input type="time" name="end_time" required
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Reason -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                    <textarea name="reason" rows="3" placeholder="Enter reason..."
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
                </div>

                <!-- Attachment (optional) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Attachment</label>
                    <input type="file" name="attachment_ot" accept=".pdf,.jpg,.jpeg,.png"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <!-- Submit -->
                <button type="submit"
                    class="w-full bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition duration-200 font-semibold text-lg">
                    Submit Overtime Request
                </button>
            </form>
        </div>
    </div>
</div>
