<?php
/*
 * @author Anakeen
 * @license http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License
 * @package FDL
*/

namespace Dcp\Offline;

class OfflineClientBuilder
{
    const targets_path = 'share/offline/targets';
    const xulruntimes_path = 'share/offline/xulruntimes';
    const xulapp_path = 'share/offline/xulapp';
    const xulapp_appini = 'share/offline/xulapp/application.ini';
    const customize_release = 'share/offline/customize/RELEASE';
    
    private $output_dir = '.';
    private $opts = array();
    
    private $targets_path = '';
    private $xulruntimes_path = '';
    private $xulapp_path = '';
    private $xulapp_appini = '';
    private $customize_release = '';
    
    public $error = '';
    
    public function __construct($output_dir = '.', $opts = array())
    {
        $this->output_dir = $output_dir;
        $this->opts = $opts;
        $this->_initPath();
        return $this;
    }
    
    private function _initPath()
    {
        include_once ('WHAT/Lib.Prefix.php');
        
        global $pubdir;
        
        $this->targets_path = sprintf('%s/%s', $pubdir, self::targets_path);
        $this->xulruntimes_path = sprintf('%s/%s', $pubdir, self::xulruntimes_path);
        $this->xulapp_path = sprintf('%s/%s', $pubdir, self::xulapp_path);
        $this->xulapp_appini = sprintf('%s/%s', $pubdir, self::xulapp_appini);
        $this->customize_release = sprintf('%s/%s', $pubdir, self::customize_release);
        
        return true;
    }
    
    public function build($buildid, $os, $arch, $prefix, $outputFile, $mar_basename = '')
    {
        include_once ('WHAT/Lib.Common.php');
        
        global $pubdir;
        
        if (strpos($os, '/') !== false) {
            $this->error = sprintf("Malformed OS '%s' (cannot contain '/').", $os);
            return false;
        }
        if (strpos($arch, '/') !== false) {
            $this->error = sprintf("Marlformed architecture '%s' (cannot contain '/').", $arch);
            return false;
        }
        
        $target_dir = sprintf("%s/%s/%s", $this->targets_path, $os, $arch);
        if (!is_dir($target_dir)) {
            $this->error = sprintf("Target directory '%s' is not a valid directory.", $target_dir);
            return false;
        }
        $build_script = sprintf('%s/build.sh', $target_dir);
        if (!is_file($build_script)) {
            $this->error = sprintf("Build script '%s' is not a valid file.", $build_script);
            return false;
        }
        if (!is_executable($build_script)) {
            $this->error = sprintf("Build script '%s' is not executable.", $build_script);
            return false;
        }
        
        if (!is_dir($this->output_dir)) {
            $this->error = sprintf("Output dir '%s' does not exists.", $this->output_dir);
            return false;
        }
        if (!is_writable($this->output_dir)) {
            $this->error = sprintf("Output dir '%s' is not writable.", $this->output_dir);
            return false;
        }
        
        $tmpfile = tempnam(getTmpDir() , __CLASS__);
        if ($tmpfile === false) {
            $this->error = sprintf("Could not create temporary file.");
            return false;
        }
        
        $prefix = $this->expandFilename($prefix, array(
            'OS' => $os,
            'ARCH' => $arch
        ));
        $outputFile = sprintf('%s/%s', $this->output_dir, $this->expandFilename($outputFile, array(
            'OS' => $os,
            'ARCH' => $arch
        )));
        
        $envBackup = $this->getEnv(array(
            'wpub',
            'CUSTOMIZE_DIR',
            'APP_VERSION',
            'APP_BUILDID',
            'CLIENTS_DIR',
            'APP_UPDATE_ENABLED',
            'OFFLINE_SERVER_VERSION',
            'DCPOFFLINE_URL_BROWSER',
            'DCPOFFLINE_URL_DATA',
            'DCPOFFLINE_URL_UPDATE'
        ));
        $env = array();
        $env['wpub'] = $pubdir;
        if (isset($this->opts['CUSTOMIZE_DIR'])) {
            $env['CUSTOMIZE_DIR'] = $this->opts['CUSTOMIZE_DIR'];
        }
        $env['APP_VERSION'] = $this->getOfflineInfo('Version.Customize');
        $env['APP_BUILDID'] = $buildid;
        $env['CLIENTS_DIR'] = $this->output_dir;
        $env['APP_UPDATE_ENABLED'] = getParam('OFFLINE_CLIENT_UPDATE_ENABLED');
        $env['OFFLINE_SERVER_VERSION'] = $this->getOfflineServerVersion();
        $env['DCPOFFLINE_URL_BROWSER'] = getParam('DCPOFFLINE_URL_BROWSER');
        $env['DCPOFFLINE_URL_DATA'] = getParam('DCPOFFLINE_URL_DATA');
        $env['DCPOFFLINE_URL_UPDATE'] = getParam('DCPOFFLINE_URL_UPDATE');
        $this->setEnv($env);
        
        $cmd = sprintf('%s %s %s %s > %s 2>&1', escapeshellarg($build_script) , escapeshellarg($prefix) , escapeshellarg($outputFile) , escapeshellarg($mar_basename) , escapeshellarg($tmpfile));
        $out = system($cmd, $ret);
        if ($ret !== 0) {
            $this->error = sprintf("Error building client for {os='%s', arch='%s', prefix='%s', outputFile='%s', basename='%s'}: %s", $os, $arch, $prefix, $outputFile, $mar_basename, file_get_contents($tmpfile));
        }
        
        $this->setEnv($envBackup);
        
        unlink($tmpfile);
        return ($ret === 0) ? true : false;
    }
    
    private function getEnv($varList = array())
    {
        $env = array();
        foreach ($varList as $var) {
            $env[] = array(
                $var => getenv($var)
            );
        }
        return $env;
    }
    
    private function setEnv($env = array())
    {
        foreach ($env as $var => $value) {
            if ($value === false) {
                putenv($var);
            } else {
                putenv(sprintf('%s=%s', $var, $value));
            }
        }
    }
    
    public function buildAll($buildid = '')
    {
        if ($buildid == '') {
            $buildid = strftime("%Y%m%d%H%M%S");
        }
        foreach ($this->getOsArchList() as $spec) {
            
            $argv = array(
                0 => $buildid,
                1 => $spec['os'],
                2 => $spec['arch'],
                3 => $spec['prefix'],
                4 => $spec['file'],
                5 => $spec['mar_basename']
            );
            
            $ret = call_user_func_array(array(
                $this,
                'build'
            ) , $argv);
            if ($ret === false) {
                $this->error = sprintf("Error building {os:'%s', arch:'%s'}: %s", $spec['os'], $spec['arch'], $this->error);
                return false;
            }
        }
        return true;
    }
    
    public function getOsArchList()
    {
        include_once ('WHAT/Lib.Prefix.php');
        
        global $pubdir;
        
        $osArchList = array(
            array(
                'title' => 'Linux (i686)',
                'os' => 'linux',
                'arch' => 'i686',
                'icon' => 'icon-linux.png',
                'prefix' => 'dynacase-offline-%VERSION%',
                'file' => 'dynacase-offline-linux-i686.tar.gz',
                'mar_basename' => 'dynacase-offline-linux-i686'
            ) ,
            array(
                'title' => 'Linux (x86_64)',
                'os' => 'linux',
                'arch' => 'x86_64',
                'icon' => 'icon-linux.png',
                'prefix' => 'dynacase-offline-%VERSION%',
                'file' => 'dynacase-offline-linux-x86_64.tar.gz',
                'mar_basename' => 'dynacase-offline-linux-x86_64'
            ) ,
            array(
                'title' => 'Mac OS X (universal)',
                'os' => 'mac',
                'arch' => 'universal',
                'icon' => 'icon-mac.png',
                'prefix' => 'dynacase-offline-%VERSION%',
                'file' => 'dynacase-offline-mac-universal.zip',
                'mar_basename' => 'dynacase-offline-mac-universal'
            ) ,
            array(
                'title' => 'Windows XP/Vista/7 (EXE 32 bits)',
                'os' => 'win',
                'arch' => '32',
                'icon' => 'icon-win.png',
                'prefix' => 'dynacase-offline-%VERSION%',
                'file' => 'dynacase-offline-win-32.exe',
                'mar_basename' => 'dynacase-offline-win-32'
            ) ,
            array(
                'title' => 'Windows XP/Vista/7 (Zip 32 bits)',
                'os' => 'win',
                'arch' => '32_zip',
                'icon' => 'icon-win.png',
                'prefix' => 'dynacase-offline-%VERSION%',
                'file' => 'dynacase-offline-win-32.zip',
                'mar_basename' => 'dynacase-offline-win-32'
            )
        );
        
        $local_osArchList = sprintf('%s/OFFLINE/local_osArchList.php', $pubdir);
        if (file_exists($local_osArchList)) {
            include_once ($local_osArchList);
        }
        
        return $osArchList;
    }
    
    public function expandFilename($filename, $keymap = array())
    {
        $keymap['VERSION'] = $this->getOfflineInfo('Version.Customize');
        
        foreach ($keymap as $k => $v) {
            $filename = str_replace(sprintf('%%%s%%', $k) , $v, $filename);
        }
        
        return $filename;
    }
    
    public static function test()
    {
        $outputDir = '/tmp';
        $ocb = new OfflineClientBuilder($outputDir);
        $buildid = strftime("%Y%m%d%H%M%S");
        
        foreach ($ocb->getOsArchList() as $spec) {
            
            $argv = array(
                0 => $buildid,
                1 => $spec['os'],
                2 => $spec['arch'],
                3 => $spec['prefix'],
                4 => $spec['file'],
                5 => $spec['mar_basename']
            );
            
            print sprintf("Building %s/%s... ", $argv[0], $argv[1]);
            $ret = call_user_func_array(array(
                $ocb,
                'build'
            ) , $argv);
            if ($ret === false) {
                print "[ERROR]\n";
                print sprintf("%s\n", $ocb->error);
                print "\n";
            } else {
                print "[OK]\n";
            }
        }
    }
    
    public function getOfflineInfo($info = 'Version')
    {
        $conf = parse_ini_file($this->xulapp_appini, true);
        if (!is_array($conf)) {
            return false;
        }
        switch ($info) {
            case 'Version.Customize':
                $customizeRelease = $this->getCustomizeRelease();
                if (isset($conf['App']['Version'])) {
                    return sprintf("%s.%d", $conf['App']['Version'], $customizeRelease);
                }
                break;

            default:
                if (isset($conf['App'][$info])) {
                    return $conf['App'][$info];
                }
        }
        return false;
    }
    
    public function getCustomizeRelease()
    {
        $customizeRelease = 0;
        if (!is_file($this->customize_release)) {
            return $customizeRelease;
        }
        if (preg_match('/^\s*(?<release>\d+)/', file_get_contents($this->customize_release) , $m)) {
            $customizeRelease = $m['release'];
        }
        return $customizeRelease;
    }
    /**
     * Get the version of the OFFLINE server application.
     *
     * @return string $version (e.g. "1.2.3-4")
     */
    public function getOfflineServerVersion()
    {
        include_once ('WHAT/Class.Application.php');
        $app = new \Application();
        $app->Set('OFFLINE', $core);
        $version = $app->getParam('VERSION');
        return $version;
    }
}
