<?php
require_once 'config.php';

// ONLY ADMIN CAN ACCESS
if (!isAdmin()) {
    header('Location: dashboard.php');
    exit;
}

// Get all members for print view
$members = $pdo->query("SELECT * FROM members ORDER BY member_id DESC")->fetchAll();
$children = $pdo->query("SELECT * FROM children ORDER BY member_id, serial_number")->fetchAll();
$children_by_member = [];
foreach ($children as $child) {
    $children_by_member[$child['member_id']][] = $child;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Member Registration Report - Print</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        /* Print Styles - REMOVES URL, DATE, PAGE NUMBERS */
        @media print {
            /* Remove browser's default header/footer (URL, date, page number) */
            @page {
                margin: 0.3in;
                size: A4;
                /* Remove default headers/footers */
                margin-top: 0.3in;
                margin-bottom: 0.3in;
                margin-left: 0.3in;
                margin-right: 0.3in;
                /* This removes the URL */
                @top-center {
                    content: none;
                }
                @bottom-center {
                    content: none;
                }
                @top-right {
                    content: none;
                }
                @bottom-right {
                    content: none;
                }
                @top-left {
                    content: none;
                }
                @bottom-left {
                    content: none;
                }
            }
            
            body { 
                margin: 0; 
                padding: 0; 
                background: white;
            }
            
            .no-print { 
                display: none !important; 
            }
            
            .page-break { 
                page-break-after: always; 
            }
            
            .member-card { 
                page-break-inside: avoid; 
                border: 1px solid #ddd !important; 
                box-shadow: none !important;
            }
            
            .print-container {
                margin: 0;
                padding: 0;
                background: white;
                box-shadow: none;
                border-radius: 0;
            }
            
            .report-header {
                padding-top: 0;
            }
        }
        
        @page {
            margin: 0.3in;
            size: A4;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            padding: 20px; 
            background: #f5f5f5; 
        }
        
        .print-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .no-print { 
            text-align: center; 
            margin-bottom: 20px; 
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #dee2e6;
        }
        
        .no-print .btn { 
            display: inline-block; 
            padding: 10px 25px; 
            background: #8e44ad; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            border: none; 
            cursor: pointer; 
            margin: 5px; 
            font-size: 14px; 
        }
        
        .no-print .btn:hover { 
            background: #7d3c98; 
        }
        
        .no-print .btn-secondary { 
            background: #6c757d; 
        }
        
        .no-print .btn-secondary:hover { 
            background: #5a6268; 
        }
        
        .no-print .btn-success { 
            background: #27ae60; 
        }
        
        .no-print .btn-success:hover { 
            background: #229954; 
        }
        
        .no-print .btn-danger { 
            background: #e74c3c; 
        }
        
        .no-print .btn-danger:hover { 
            background: #c0392b; 
        }
        
        .report-header { 
            text-align: center; 
            border-bottom: 3px double #2c3e50; 
            padding-bottom: 15px; 
            margin-bottom: 20px; 
        }
        
        .report-header h1 { 
            font-size: 24px; 
            text-transform: uppercase; 
            color: #2c3e50; 
        }
        
        .report-header p { 
            color: #6c757d; 
            font-size: 14px; 
        }
        
        .member-card { 
            background: white; 
            border: 1px solid #dee2e6; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 20px; 
            page-break-inside: avoid; 
        }
        
        .member-card h3 { 
            background: #f8f9fa; 
            padding: 8px 12px; 
            margin: -15px -15px 15px -15px; 
            border-radius: 8px 8px 0 0; 
            color: #2c3e50; 
            border-bottom: 2px solid #dee2e6; 
        }
        
        .detail-row { 
            display: flex; 
            padding: 4px 0; 
            border-bottom: 1px solid #f0f0f0; 
        }
        
        .detail-label { 
            font-weight: bold; 
            width: 180px; 
            color: #555; 
            flex-shrink: 0; 
        }
        
        .detail-value { 
            flex: 1; 
        }
        
        .children-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            font-size: 13px; 
        }
        
        .children-table th { 
            background: #f8f9fa; 
            border: 1px solid #dee2e6; 
            padding: 6px; 
            text-align: left; 
        }
        
        .children-table td { 
            border: 1px solid #dee2e6; 
            padding: 6px; 
        }
        
        .footer { 
            text-align: center; 
            margin-top: 30px; 
            padding-top: 15px; 
            border-top: 1px solid #dee2e6; 
            color: #6c757d; 
            font-size: 12px; 
        }
        
        .badge { 
            display: inline-block; 
            padding: 2px 10px; 
            border-radius: 20px; 
            font-size: 11px; 
            font-weight: bold; 
        }
        
        .badge-male { 
            background: #3498db; 
            color: white; 
        }
        
        .badge-female { 
            background: #e74c3c; 
            color: white; 
        }
        
        .spouse-section {
            margin-top: 10px; 
            padding: 10px; 
            background: #f8f9fa; 
            border-radius: 4px;
        }
        
        .children-section {
            margin-top: 10px;
        }
        
        .info-text {
            margin-top: 10px; 
            font-size: 12px; 
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Print Controls - Hidden when printing -->
        <div class="no-print">
            <button onclick="window.print()" class="btn">🖨️ Print Now</button>
            <button onclick="window.location.href='export_pdf.php'" class="btn btn-success">📥 Download PDF</button>
            <button onclick="window.location.href='form_download.php'" class="btn btn-secondary">⬅️ Back</button>
            <hr style="margin: 15px 0;">
            <p style="color: #6c757d; font-size: 14px;">
                <strong>Tip:</strong> Press <kbd>Ctrl+P</kbd> or click the Print button above
            </p>
        </div>
        
        <!-- Report Header -->
        <div class="report-header">
            <h1>Member Registration Report</h1>
            <p>Generated on: <?= date('F d, Y H:i:s') ?> | Total Members: <?= count($members) ?></p>
        </div>
        
        <?php if (empty($members)): ?>
            <div style="text-align: center; padding: 50px; color: #6c757d;">
                <h3>No members registered yet.</h3>
            </div>
        <?php else: ?>
            <?php foreach ($members as $index => $member): ?>
                <div class="member-card">
                    <h3>#<?= $index + 1 ?> - <?= htmlspecialchars($member['full_name']) ?></h3>
                    
                    <div class="detail-row">
                        <div class="detail-label">Area Name:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['area_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">ID Number:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['id_number'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Gender:</div>
                        <div class="detail-value"><span class="badge badge-<?= strtolower($member['gender']) ?>"><?= $member['gender'] ?? 'N/A' ?></span></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Date of Birth:</div>
                        <div class="detail-value"><?= $member['date_of_birth'] ? date('F d, Y', strtotime($member['date_of_birth'])) : 'N/A' ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Marital Status:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['marital_status'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone Numbers:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['phone1'] ?? '') ?> <?= !empty($member['phone2']) ? '| ' . htmlspecialchars($member['phone2']) : '' ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Zone Department:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['zone_department'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['email'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Occupation:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['occupation'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Company:</div>
                        <div class="detail-value"><?= htmlspecialchars($member['company_name'] ?? 'N/A') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Residential Address:</div>
                        <div class="detail-value"><?= nl2br(htmlspecialchars($member['residential_address'] ?? 'N/A')) ?></div>
                    </div>
                    
                    <!-- Spouse Details -->
                    <?php if (!empty($member['spouse_full_name'])): ?>
                        <div class="spouse-section">
                            <strong>Spouse Details:</strong>
                            <div class="detail-row">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_full_name']) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">ID Number:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_id_number'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_phone1'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Profession:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_profession'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Email:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_email'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Zone:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['spouse_zone_department'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Children Details -->
                    <?php if (isset($children_by_member[$member['member_id']]) && count($children_by_member[$member['member_id']]) > 0): ?>
                        <div class="children-section">
                            <strong>Children:</strong>
                            <table class="children-table">
                                <thead>
                                    <tr>
                                        <th style="width: 10%;">#</th>
                                        <th style="width: 30%;">Full Name</th>
                                        <th style="width: 25%;">Date of Birth</th>
                                        <th style="width: 15%;">Gender</th>
                                        <th style="width: 20%;">Profession/Department</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($children_by_member[$member['member_id']] as $child): ?>
                                        <tr>
                                            <td><?= $child['serial_number'] ?></td>
                                            <td><?= htmlspecialchars($child['full_name']) ?></td>
                                            <td><?= $child['date_of_birth'] ? date('F d, Y', strtotime($child['date_of_birth'])) : 'N/A' ?></td>
                                            <td><?= $child['gender'] ?? 'N/A' ?></td>
                                            <td><?= htmlspecialchars($child['profession_zone'] ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Next of Kin -->
                    <?php if (!empty($member['next_of_kin_name'])): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 4px solid #ffc107;">
                            <strong>Next of Kin / Emergency Contact:</strong>
                            <div class="detail-row">
                                <div class="detail-label">Name:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_name']) ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Relationship:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_relationship'] ?? 'N/A') ?></div>
                            </div>
                            <div class="detail-row">
                                <div class="detail-label">Phone:</div>
                                <div class="detail-value"><?= htmlspecialchars($member['next_of_kin_phone'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Office Use -->
                    <div style="margin-top: 10px; padding: 10px; background: #e8f4fd; border-radius: 4px; border-left: 4px solid #3498db;">
                        <strong>Office Use:</strong>
                        <div class="detail-row">
                            <div class="detail-label">Received By:</div>
                            <div class="detail-value"><?= htmlspecialchars($member['received_by'] ?? 'N/A') ?></div>
                        </div>
                        <div class="detail-row">
                            <div class="detail-label">Date Received:</div>
                            <div class="detail-value"><?= $member['date_received'] ? date('F d, Y', strtotime($member['date_received'])) : 'N/A' ?></div>
                        </div>
                    </div>
                    
                    <div class="info-text">
                        <span>Registered: <?= date('Y-m-d H:i', strtotime($member['created_at'])) ?></span>
                        <?php if ($member['received_by']): ?>
                            <span style="margin-left: 15px;">Received by: <?= htmlspecialchars($member['received_by']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <div class="footer">
            <p>Member Registration System | Generated: <?= date('Y-m-d H:i:s') ?> | Total: <?= count($members) ?> members</p>
        </div>
    </div>
    
    <script>
        console.log('Print report loaded. Click Print button or press Ctrl+P');
    </script>
</body>
</html>