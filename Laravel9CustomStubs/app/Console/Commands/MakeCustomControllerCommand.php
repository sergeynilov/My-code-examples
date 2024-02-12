<?php

namespace App\Console\Commands;

use App\Enums\StubFileType;
use App\Exceptions\StubsGeneratorError;
use App\Library\Services\Interfaces\StubsInterface;
use App\Library\StubCodeContainer;
use App\Library\StubsHelper;
use Illuminate\Console\Command;
use \Illuminate\Filesystem\Filesystem;
use Str;

/**
 * Create a new CRUD Controller based on stubs/custom.controllers.stub file.
 *
 * @param {name?} - Optional. Model name
 * @param {showDone?} - Optional. if set then done/undone methods are added
 * @param {transactionWithInterface?} - Optional. if set then transaction with interface class are used
 * @param {help?} - Optional. if set then help text is shown, but new controller is not created
 */
class MakeCustomControllerCommand extends Command implements StubsInterface
{
    /**
     * The name and signature of the console command with parameters.
     *
     * @var string
     */
    protected $signature = 'make:custom-controller {name?} {showDone?} {transactionWithInterface?} {help?}';

    protected $description = 'Make custom controller class based on stub stubs/custom.controllers.stub file';

    /**
     * Filesystem instance
     *
     * @var Filesystem
     */
    protected \Illuminate\Filesystem\Filesystem $fs;

    /**
     * Stubs Helper file with methods
     *
     * @var stubsHelper class
     */
    protected $stubsHelper;

    /**
     * If need to overwrite destination file if it exists
     *
     * @var bool
     */
    protected $overwriteFile = true;

    /**
     * Standard DB transaction using or custom transaction with interface
     *
     * @var array $transactionReplacements
     */
    protected array $transactionReplacements
        = [
            'use App\Repositories\Interfaces\DBTransactionInterface;' => 'use DB;',
            '$this->dbTransaction->begin();' => 'DB::beginTransaction();',
            '$this->dbTransaction->commit();' => 'DB::commit();',
            '$this->dbTransaction->rollback();' => 'DB::rollback();',
        ];

    /**
     * In help mode need to show help text and exit
     *
     * @var bool
     */
    protected bool $consoleHelp;

    /**
     * To leave/hide show Done/Complete methods code block
     *
     * @var bool
     */
    protected bool $showDone;

    /**
     * To use custom transaction Interface class(if true) OR standard DB transaction methods;
     *
     * @var bool
     */
    protected bool $transactionWithInterface;

    /**
     * if to show Logging debugging info in actions
     *
     * @var bool
     */
    protected bool $showLogInfo;

    /**
     * Working with file
     *
     * @param Filesystem $fs
     */
    public function __construct(\Illuminate\Filesystem\Filesystem $fs)
    {
        parent::__construct();

        $this->fs = $fs;
        $this->stubsHelper = new StubsHelper($this->fs);
    }

    /**
     * Execute the console command to create a new controller file
     */
    public function handle()
    {
        try {
            $destinationPath = $this->stubsHelper->getDestinationPatternFilePath(StubFileType::CUSTOM_CONTROLLER,
                $this->argument('name'));
            if (empty($destinationPath)) {
                $this->error("Destination file path is not defined");
                return;
            }
            $this->stubsHelper->createDestinationDirectory(dirname($destinationPath));
            $resultingCode = $this->generateResultingCode();

            if ( ! $this->fs->exists($destinationPath) or $this->overwriteFile) {
                $this->fs->put($destinationPath, $resultingCode);
                $this->info("File : {$destinationPath} created");
            } else {
                $this->error("File : {$destinationPath} already exits");
            }
        } catch (StubsGeneratorError $e) {
            $this->error("Stubs generator error : " . $e->getMessage());
        } catch (\Exception | \Error $e) {
            $this->error($e->getMessage());
        }
    }

    /**
     **
     * Map the stub variables present in stub to its value
     *
     * @return array
     *
     */
    public function getStubVars(): array
    {
        $singularClassName = $this->stubsHelper->getSingularClassName($this->argument('name'));
        $arguments = $this->arguments();

        $this->consoleHelp = Str::lower($arguments['name']) === 'help';
        if ($this->consoleHelp) {
            $stubContainer = new StubCodeContainer();
            foreach ($stubContainer->getCRUDControllerHelp() as $cRUDControllerHelpLine) {
                $this->info($cRUDControllerHelpLine);
            };
            die('Help shown');
        }

        $this->showDone = in_array('showDone', $arguments);
        $this->transactionWithInterface = in_array('transactionWithInterface', $arguments);
        $this->showLogInfo = in_array('showLogInfo', $arguments);

        return [
            'namespacePath' => 'App\Http\Controllers',
            'ucFirstClass' => Str::ucfirst($singularClassName),   // ProductCategory
            'pluralUcFirstClass' => Str::plural(Str::ucfirst($singularClassName)),   // ProductCategories
            'camelCaseClass' => Str::of($singularClassName)->camel(),   // productCategory
            'pluralCamelCaseClass' => Str::plural(Str::of($singularClassName)->camel()),   // productCategories
            'lowercaseClass' => Str::lower($singularClassName),   // productcategory
            'uppercaseClass' => Str::upper(Str::snake($singularClassName)), // PRODUCT_CATEGORY
        ];
    }

    /**
     * Replace variables of stub with the class value
     *
     * @param $stub
     * @param array $stubVariables
     *
     * @return string
     */
    public function getStubContents($stub, $stubVariables = []): string
    {
        $contents = file_get_contents($stub);
        if ($this->showDone) { // Need to leave Show Done code block, but to clear "// SHOW_DONE_BLOCK s" tag
            $contents = preg_replace('~[^\S\n]*\/\/\s*SHOW_DONE_BLOCK_.*\n~i', '', $contents);
        }

        if ($this->transactionWithInterface) { // Need code of transaction with interface class used
            $contents = preg_replace('~\/\/[\s]*SHOW_DB_TRANSACTION_VAR_DECLARE_[\w]+~i', '', $contents);
            $contents = preg_replace('~\/\/[\s]*SHOW_DB_TRANSACTION_CLASS_INJECTION[\w]+~i', '', $contents);
            foreach ($this->transactionReplacements as $interfaceTransaction => $dBTransaction) {
                $contents = str_replace($dBTransaction, $interfaceTransaction, $contents);  // DONE
            }
        }

        if ( ! $this->transactionWithInterface) {
            $contents = preg_replace('~\/\/\s*SHOW_DB_TRANSACTION_CLASS_INJECTION_START.*\/\/[\s]*SHOW_DB_TRANSACTION_CLASS_INJECTION_END~i',
                '', $contents);  // DONE
            $contents = preg_replace('~\/\/[\s]*SHOW_DB_TRANSACTION_VAR_DECLARE_START.*?\/\/[\s]*SHOW_DB_TRANSACTION_VAR_DECLARE_END~isu',
                '', $contents); // DONE
        }

        if ($this->showLogInfo) {
            $contents = preg_replace('~[^\S\n]*\/\/\s*SHOW_LOG_INFO_.*\n+~i', '', $contents);
        }
        if ( ! $this->showLogInfo) {
            $contents = preg_replace('~[^\S\n]*\/\/\s*SHOW_LOG_INFO_.*\n+~i', '', $contents);
        }

        foreach ($stubVariables as $search => $replace) {
            $contents = preg_replace('~{{[\s]*' . $search . '[\s]*}}~i', $replace, $contents);
        }
        return $this->stubsHelper->removeAdditiveCRLFs($contents);
    }

    /**
     * Get the stub path and the stub variables and generate resulting code string
     *
     * @return string
     *
     */
    public function generateResultingCode(): string
    {
        return $this->getStubContents($this->stubsHelper->getStubSourceFilePath(StubFileType::CUSTOM_CONTROLLER),
            $this->getStubVars());
    }

}
