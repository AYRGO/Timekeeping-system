<!-- 201 Checklist Tab -->
<div id="checklist" class="tab-content">
    <div class="bg-gradient-to-br from-white to-gray-50 rounded-xl shadow-lg p-8">
        <!-- Header Section -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-clipboard-list text-blue-600 mr-3"></i>
                    201 File Checklist
                </h2>
                <p class="text-gray-600">Upload and manage required employment documents</p>
            </div>
            
            <!-- Progress Summary -->
            <div class="bg-white rounded-lg p-4 shadow-sm border border-gray-200">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600 mb-1">
                        <?php 
                        $totalFields = count($documents);
                        $completedFields = 0;
                        foreach ($documents as $field => $label) {
                            if (!empty($checklist[$field])) $completedFields++;
                        }
                        echo $completedFields . '/' . $totalFields;
                        ?>
                    </div>
                    <div class="text-sm text-gray-500">Documents</div>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                             style="width: <?= ($completedFields / $totalFields) * 100 ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
        
        <form method="post" enctype="multipart/form-data" class="space-y-8">
            <input type="hidden" name="upload_documents" value="1">
            
            <!-- Basic Employment Documents -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-briefcase text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Basic Employment Documents</h3>
                        <p class="text-sm text-gray-500">Essential documents for employment verification</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php 
                    $basicDocs = ['letter_offer', 'employment_contract', 'employment_adjustment_form'];
                    foreach ($basicDocs as $field): 
                        if (isset($documents[$field])):
                    ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-blue-300 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-800"><?= $documents[$field] ?></label>
                            <?php if (!empty($checklist[$field])): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="<?= $field ?>[]" multiple 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        
                        <?php if (!empty($checklist[$field])): ?>
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-600 mb-2">Uploaded Files:</div>
                                <?php foreach (explode(',', $checklist[$field]) as $file): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <a href="../uploads/checklist/<?= htmlspecialchars(trim($file)) ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                            <i class="fas fa-file-alt mr-2"></i>
                                            <span class="truncate max-w-40"><?= htmlspecialchars(trim($file)) ?></span>
                                        </a>
                                        <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=<?= $field ?>&file=<?= urlencode(trim($file)) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this file?');" 
                                           class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>

            <!-- Medical & Clearances -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-user-md text-green-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Medical & Clearances</h3>
                        <p class="text-sm text-gray-500">Health certificates and background clearances</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php 
                    $medicalDocs = ['medical', 'nbi_clearance'];
                    foreach ($medicalDocs as $field): 
                        if (isset($documents[$field])):
                    ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-green-300 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-800"><?= $documents[$field] ?></label>
                            <?php if (!empty($checklist[$field])): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="<?= $field ?>[]" multiple 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent bg-white" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        
                        <?php if (!empty($checklist[$field])): ?>
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-600 mb-2">Uploaded Files:</div>
                                <?php foreach (explode(',', $checklist[$field]) as $file): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <a href="../uploads/checklist/<?= htmlspecialchars(trim($file)) ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                            <i class="fas fa-file-alt mr-2"></i>
                                            <span class="truncate max-w-40"><?= htmlspecialchars(trim($file)) ?></span>
                                        </a>
                                        <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=<?= $field ?>&file=<?= urlencode(trim($file)) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this file?');" 
                                           class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>

            <!-- Educational Documents -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-graduation-cap text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Educational Documents</h3>
                        <p class="text-sm text-gray-500">Academic credentials and certifications</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-purple-300 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-800"><?= $documents['diploma_tor'] ?></label>
                            <?php if (!empty($checklist['diploma_tor'])): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="diploma_tor[]" multiple 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        
                        <?php if (!empty($checklist['diploma_tor'])): ?>
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-600 mb-2">Uploaded Files:</div>
                                <?php foreach (explode(',', $checklist['diploma_tor']) as $file): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <a href="../uploads/checklist/<?= htmlspecialchars(trim($file)) ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                            <i class="fas fa-file-alt mr-2"></i>
                                            <span class="truncate max-w-40"><?= htmlspecialchars(trim($file)) ?></span>
                                        </a>
                                        <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=diploma_tor&file=<?= urlencode(trim($file)) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this file?');" 
                                           class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Government IDs & Documents -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-id-card text-red-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Government IDs & Documents</h3>
                        <p class="text-sm text-gray-500">Official government-issued identification and documents</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php 
                    $govDocs = ['psa', 'sss', 'tin', 'philhealth', 'pagibig'];
                    foreach ($govDocs as $field): 
                        if (isset($documents[$field])):
                    ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-red-300 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-800"><?= $documents[$field] ?></label>
                            <?php if (!empty($checklist[$field])): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="<?= $field ?>[]" multiple 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent bg-white" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        
                        <?php if (!empty($checklist[$field])): ?>
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-600 mb-2">Uploaded Files:</div>
                                <?php foreach (explode(',', $checklist[$field]) as $file): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <a href="../uploads/checklist/<?= htmlspecialchars(trim($file)) ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                            <i class="fas fa-file-alt mr-2"></i>
                                            <span class="truncate max-w-40"><?= htmlspecialchars(trim($file)) ?></span>
                                        </a>
                                        <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=<?= $field ?>&file=<?= urlencode(trim($file)) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this file?');" 
                                           class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>

            <!-- Valid IDs & Special Documents -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                        <i class="fas fa-address-card text-orange-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Valid IDs & Special Documents</h3>
                        <p class="text-sm text-gray-500">Additional identification and special circumstances documents</p>
                    </div>
                </div>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php 
                    $idDocs = ['valid_id', 'Valid_id_2', 'solo_parent_id', 'coe'];
                    foreach ($idDocs as $field): 
                        if (isset($documents[$field])):
                    ?>
                    <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 hover:border-orange-300 transition-colors">
                        <div class="flex items-center justify-between mb-3">
                            <label class="text-sm font-medium text-gray-800"><?= $documents[$field] ?></label>
                            <?php if (!empty($checklist[$field])): ?>
                                <span class="px-2 py-1 bg-green-100 text-green-800 text-xs rounded-full">
                                    <i class="fas fa-check mr-1"></i>Complete
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs rounded-full">
                                    <i class="fas fa-clock mr-1"></i>Pending
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-3">
                            <input type="file" name="<?= $field ?>[]" multiple 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent bg-white" 
                                   accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" />
                        </div>
                        
                        <?php if (!empty($checklist[$field])): ?>
                            <div class="space-y-2">
                                <div class="text-xs font-medium text-gray-600 mb-2">Uploaded Files:</div>
                                <?php foreach (explode(',', $checklist[$field]) as $file): ?>
                                    <div class="flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                                        <a href="../uploads/checklist/<?= htmlspecialchars(trim($file)) ?>" target="_blank" 
                                           class="text-blue-600 hover:text-blue-800 flex items-center text-sm">
                                            <i class="fas fa-file-alt mr-2"></i>
                                            <span class="truncate max-w-40"><?= htmlspecialchars(trim($file)) ?></span>
                                        </a>
                                        <a href="?id=<?= $employeeId ?>&delete_attachment=1&field=<?= $field ?>&file=<?= urlencode(trim($file)) ?>" 
                                           onclick="return confirm('Are you sure you want to delete this file?');" 
                                           class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
            
            <!-- Submit Button -->
            <div class="flex justify-end pt-6 border-t border-gray-200">
                <button type="submit" class="bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white px-8 py-3 rounded-lg transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>Upload Documents
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Custom Styles -->
<style>
    .truncate {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    input[type="file"] {
        transition: all 0.2s ease;
    }
    
    input[type="file"]:hover {
        border-color: #9CA3AF;
    }
    
    .tab-content {
        animation: fadeIn 0.3s ease-in-out;
    }
    
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>
