<?php
/**
 * Migration: Change defaultid to is_default in vtiger_currency_info
 * 
 * This migration:
 * 1. Adds is_default column
 * 2. Migrates data (defaultid = -11 -> is_default = 1)
 * 3. Adds index
 * 4. Removes old defaultid column
 */

chdir(__DIR__);
define('ROOT_DIRECTORY', getcwd() !== DIRECTORY_SEPARATOR ? getcwd() : '');
require ROOT_DIRECTORY . '/vendor/autoload.php';
require ROOT_DIRECTORY . '/vendor/yiisoft/yii2/Yii.php';
require ROOT_DIRECTORY . '/config/api.php';
require ROOT_DIRECTORY . '/config/config.php';
\App\Core\AppConfig::init($API_CONFIG);
\App\Loader::register();

echo "=== Migration: defaultid -> is_default ===\n\n";

$db = \App\Db\Db::getInstance();

try {
    // Check if is_default column already exists
    $columns = $db->createCommand("SHOW COLUMNS FROM vtiger_currency_info LIKE 'is_default'")->queryAll();
    if (!empty($columns)) {
        echo "⚠️  Column 'is_default' already exists. Migration may have already been run.\n";
        echo "Do you want to continue? This will update data and remove defaultid column. (y/n): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        if (trim($line) !== 'y') {
            echo "Migration cancelled.\n";
            exit(0);
        }
        fclose($handle);
    }
    
    echo "Step 1: Adding is_default column...\n";
    $db->createCommand("
        ALTER TABLE vtiger_currency_info 
        ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0 AFTER currency_status
    ")->execute();
    echo "✓ Column added\n\n";
    
    echo "Step 2: Migrating data (defaultid = -11 -> is_default = 1)...\n";
    $result = $db->createCommand("
        UPDATE vtiger_currency_info 
        SET is_default = 1 
        WHERE defaultid = -11
    ")->execute();
    echo "✓ Updated $result row(s) to is_default = 1\n\n";
    
    echo "Step 3: Setting remaining rows to is_default = 0...\n";
    $result = $db->createCommand("
        UPDATE vtiger_currency_info 
        SET is_default = 0 
        WHERE defaultid != -11 OR defaultid IS NULL
    ")->execute();
    echo "✓ Updated $result row(s) to is_default = 0\n\n";
    
    echo "Step 4: Adding index on is_default...\n";
    try {
        $db->createCommand("
            ALTER TABLE vtiger_currency_info 
            ADD INDEX idx_is_default (is_default)
        ")->execute();
        echo "✓ Index added\n\n";
    } catch (\Exception $e) {
        // Index may already exist
        if (strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⚠️  Index already exists, skipping...\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "Step 5: Removing old defaultid column...\n";
    $db->createCommand("
        ALTER TABLE vtiger_currency_info 
        DROP COLUMN defaultid
    ")->execute();
    echo "✓ Column defaultid removed\n\n";
    
    // Clear currency cache
    echo "Step 6: Clearing currency cache...\n";
    \App\Cache\Cache::delete('AllCurrency', 'All');
    \App\Cache\Cache::delete('Currency', 'List');
    echo "✓ Cache cleared\n\n";
    
    // Verify migration
    echo "Step 7: Verifying migration...\n";
    $defaultCurrency = $db->createCommand("
        SELECT id, currency_name, currency_code, is_default 
        FROM vtiger_currency_info 
        WHERE is_default = 1
    ")->queryOne();
    
    if ($defaultCurrency) {
        echo "✓ Default currency found: {$defaultCurrency['currency_name']} ({$defaultCurrency['currency_code']}) - ID: {$defaultCurrency['id']}\n\n";
    } else {
        echo "⚠️  WARNING: No default currency found! This may cause errors.\n\n";
    }
    
    // Check if defaultid column still exists
    $columns = $db->createCommand("SHOW COLUMNS FROM vtiger_currency_info LIKE 'defaultid'")->queryAll();
    if (empty($columns)) {
        echo "✓ Column 'defaultid' successfully removed\n\n";
    } else {
        echo "⚠️  WARNING: Column 'defaultid' still exists!\n\n";
    }
    
    echo "=== Migration completed successfully! ===\n";
    echo "\nNext steps:\n";
    echo "1. Update code to use is_default instead of defaultid\n";
    echo "2. Test currency functionality\n";
    echo "3. Check logs for any errors\n";
    
} catch (\Exception $e) {
    echo "\n❌ Migration failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "\nYou may need to manually rollback changes.\n";
    exit(1);
}

