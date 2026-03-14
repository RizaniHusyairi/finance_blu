<?php
$files = scandir('database/migrations');
$time = time();
foreach($files as $i => $file) {
    if(strpos($file, 'create') !== false && strpos($file, '0001_01_01') === false) {
        // Strip out the old timestamp
        $baseName = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $file);
        
        // Define order manually to ensure constraints work
        // 1. permissions, employees, suppliers, budgets
        // 2. contracts
        // 3. contract_addendums, contract_terms
        // 4. transactions
        // 5. transaction_taxes, approval_logs, bku_logs
        $order = [
            'create_permission_tables.php' => 10,
            'create_employees_table.php' => 20,
            'create_suppliers_table.php' => 21,
            'create_budgets_table.php' => 22,
            'create_contracts_table.php' => 30,
            'create_contract_addendums_table.php' => 40,
            'create_contract_terms_table.php' => 41,
            'create_transactions_table.php' => 50,
            'create_transaction_taxes_table.php' => 60,
            'create_approval_logs_table.php' => 61,
            'create_bku_logs_table.php' => 62,
        ];
        
        $prefix = isset($order[$baseName]) ? $order[$baseName] : 99;
        $timestamp = date('Y_m_d_His', $time + $prefix);
        $newName = $timestamp . '_' . $baseName;
        
        rename('database/migrations/'.$file, 'database/migrations/'.$newName);
        echo "Renamed $file to $newName\n";
    }
}
