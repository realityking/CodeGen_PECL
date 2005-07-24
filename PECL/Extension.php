<?php
/**
 * A class that generates PECL extension soure and documenation files
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id$
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "System.php";

require_once "CodeGen/Extension.php";
    
require_once "CodeGen/PECL/Release.php";

require_once "CodeGen/PECL/Element.php";
require_once "CodeGen/PECL/Element/Constant.php";
require_once "CodeGen/PECL/Element/Function.php";
require_once "CodeGen/PECL/Element/Resource.php";
require_once "CodeGen/PECL/Element/Ini.php";
require_once "CodeGen/PECL/Element/Global.php";
require_once "CodeGen/PECL/Element/Logo.php";
require_once "CodeGen/PECL/Element/Test.php";
require_once "CodeGen/PECL/Element/Class.php";
require_once "CodeGen/PECL/Element/Stream.php";

require_once "CodeGen/PECL/Dependency/With.php";
require_once "CodeGen/PECL/Dependency/Lib.php";
require_once "CodeGen/PECL/Dependency/Header.php";

/**
 * A class that generates PECL extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_PECL_Extension 
    extends CodeGen_Extension
{
    /**
    * Current version number
    *
    * @access public
    * @return string version
    */
    static function version() 
    {
      return "0.9.0rc5";
    }

    /**
    * Copyright message
    *
    * @access public
    * @return string version
    */
    static function copyright() 
    {
      return "Copyright (c) 2003-2005 Hartmut Holzgraefe";
    }

    // {{{ member variables

    


    /**
     * The public PHP functions defined by this extension
     *
     * @var array
     */
    var $functions = array();
    
    /**
     * The extensions internal functions like MINIT 
     *
     * @var array
     */
    var $internalFunctions = array();
        
    /**
     * The constants defined by this extension
     *
     * @var array
     */
    var $constants = array();
    
    /**
     * The PHP classes defined by this extension
     *
     * @var array
     */
    var $classes = array();
    
    /**
     * The extensions php.ini parameters
     *
     * @var array
     */
    var $phpini    = array();
    
    /**
     * The extensions internal global variables
     *
     * @var array
     */
    var $globals    = array();

    /**
     * The PHP resources defined by this extension
     *
     * @var array
     */
    var $resources = array();

    /**
     * The package files created by this extension
     *
     * @var array
     */
    var $packageFiles = array();

    /**
     * Code snippets
     *
     * @var array
     */
    var $code = array();


    /**
     * Makefile fragments
     *
     * @var array
     */
    var $makefragment = array();

    /** 
     * Custom test cases
     *
     * @var array
     */
    var $testcases = array();


    /**
     * phpinfo logos
     *
     * @var    string
     * @access private
     */
    var $logos = array();


    /**
     * Makefile fragments
     *
     * @var    array
     * @access private
     */
    var $makefragments = array();


    /**
     * config.m4 fragments
     *
     * @var    array
     * @access private
     */
    var $configfragments = array("top"=>array(), "bottom"=>array());

    /**
     * generate #line specs?
     *
     * @var     bool
     * @access  private
     */
    var $linespecs = false;

    /**
     * PHP Streams
     *
     * @var    array
     * @access private
     */
    var $streams = array();

    /**
     * External libraries
     *
     * @var    array
     * @access private
     */
    var $libs = array();

    /**
     * External header files
     *
     * @var    array
     * @access private
     */
    var $headers = array();

    /**
     * --with configure options
     * 
     * @var    array
     * @access private
     */
    var $with = array();


    // }}} 

    
    // {{{ constructor
    
    /**
     * The constructor
     *
     * @access public
     */
    function __construct() 
    {
        $this->release = new CodeGen_PECL_Release;
        
        $this->platform = new CodeGen_Tools_Platform("all");
    }
    
    // }}} 
    


    
    
    /** 
     * Add verbatim code snippet to extension
     *
     * @access public
     * @param  string  which file to put the code to
     * @param  string  where in the file the code should be put
     * @param  string  the actual code
     */
    function addCode($role, $position, $code)
    {
        if (!in_array($role, array("header", "code"))) {
            return PEAR::raiseError("'$role' is not a valid custom code role");
        }
        if (!in_array($position, array("top", "bottom"))) {
            return PEAR::raiseError("'$position' is not a valid custom code position");
        }
        $this->code[$role][$position][] = $code;
    }


    function addLib($lib) 
    {
        // TODO check dups
        $this->libs[$lib->getName()] = $lib;
    }

    function addHeader($header) 
    {
        // TODO check dups
        $this->headers[$header->getName()] = $header;
    }


    // {{{ member adding functions
    

    /**
     * Add a function to the extension
     *
     * @access public
     * @param  object   a function object
     */
    function addFunction(CodeGen_PECL_Element_Function $function)
    {
        $role = $function->getRole();
        
        switch ($role) {
        case "public":
            if (isset($this->functions[$function->name])) {
                return PEAR::raiseError("public function '{$function->name}' has been defined before");
            }
            $this->functions[$function->name] = $function;
            return true;
            
        case "private":
            return PEAR::raiseError("private functions are no longer supported, use <code> sections instead");
            
        case "internal":
            if (isset($this->functions[$function->name])) {
                return PEAR::raiseError("internal '{$function->name}' has been defined before");
            }
            $this->internalFunctions[$function->name] = $function;
            return true;
            
        default: 
            return PEAR::raiseError("unnokwn function role '$role'");
        }
    }


    /**
     * Add a PHP constant to the extension
     *
     * @access public
     * @param  object   a constant object
     */
    function addConstant($constant)
    {
        if (isset($this->constants[$constant->name])) {
            return PEAR::raiseError("constant '{$constant->name}' has been defined before");
        }
        $this->constants[$constant->name] = $constant;
        
        return true;
    }
    
    

    /**
     * Add a PHP ini directive
     *
     * @access public
     * @param  object   a phpini object
     */
    function addPhpIni($phpini)
    {
        if (isset($this->phpini[$phpini->name])) {
            return PEAR::raiseError("php.ini directive '{$phpini->name}' has been defined before");
        }
        $this->phpini[$phpini->name] = $phpini;
        
        return true;
    }


    /**
     * Add a internal global variable
     *
     * @access public
     * @param  object   a global object
     */
    function addGlobal($global)
    {
        if (isset($this->globals[$global->name])) {
            return PEAR::raiseError("global '{$global->name}' has been defined before");
        }
        $this->globals[$global->name] = $global;
        
        return true;
    }
  

    /**
     * Add a PHP resource type
     *
     * @access public
     * @param  object   a resource object
     */
    function addResource($resource)
    {
        if (isset($this->resources[$resource->name])) {
            return PEAR::raiseError("resource type '{$resource->name}' has been defined before");
        }
        $this->resources[$resource->name] = $resource;
        
        return true;
    }
    
    
    /**
     * Add a PHP class to the extension
     *
     * @access public
     * @param  object   a class object
     */
    function addClass($class)
    {
        if (isset($this->classes[$class->getName()])) {
            return PEAR::raiseError("class '".$class->getName()."' has been defined before");
        }
        $this->classes[$class->getName()] = $class;
        
        return true;
    }
    
    
    /**
     * Add a PHP stream wrapper to the extension
     *
     * @access public
     * @param  object   a stream wrapper object
     */
    function addStream($stream)
    {
        if (isset($this->streams[$stream->getName()])) {
            return PEAR::raiseError("stream '".$stream->getName()."' has been defined before");
        }
        $this->streams[$stream->getName()] = $stream;
        
        return true;
    }
    
    
    /**
     * Add a package file by type and path
     *
     * @access  public
     * @param   string  type
     * @param   string  path
     * @returns bool    success state
     */
    function addPackageFile($type, $path)
    {
        $basename = basename($path);

        if (isset($this->packageFiles[$type][$basename])) {
            return PEAR::raiseError("duplicate distribution file name '$basename'");
        }

        $this->packageFiles[$type][$basename] = $path;
        return true;
    }
    
    /** 
     * Add a --with configure option
     *
     * @access  public
     * @param   object 'With' object
     * @returns bool
     */
    function addWith($with) 
    {
        if (isset($this->with[$with->name])) {
            return PEAR::raiseError("--with-{$with->name} declared twice");
        }

        $this->with[$with->name] = $with;

        return true;
    }

    /**
     * Add a source file to be copied to the extension dir
     *
     * @access public
     * @param  string path
     */
    function addSourceFile($name) 
    {
        // TODO catch errors returned from addPackageFile

        $filename = realpath($name);

            if (!is_file($filename)) {
                return PEAR::raiseError("'$name' is not a valid file");
            }
            
            if (!is_readable($filename)) {
                return PEAR::raiseError("'$name' is not readable");
            }
            
            $pathinfo = pathinfo($filename);
            $ext = $pathinfo["extension"];

            switch ($ext) {
            case 'c':
                $this->addConfigFragment("AC_PROG_CC");
                $this->addPackageFile('code', $filename);
                break;
            case 'cpp':
            case 'cxx':
            case 'c++':
                $this->addConfigFragment("AC_PROG_CXX");
                $this->addConfigFragment("AC_LANG([C++])");
                $this->addPackageFile('code', $filename);
                break;
            case 'l':
            case 'flex':
                $this->addConfigFragment("AM_PROG_LEX");
                $this->addPackageFile('code', $filename);
                break;
            case 'y':
            case 'bison':
                $this->addConfigFragment("AM_PROG_YACC");
                $this->addPackageFile('code', $filename);
                break;
            default:
                break;
            }

            return $this->addPackageFile('copy', $filename);
    }

    /** 
     * Add phpinfo logo
     *
     * @access public
     * @param  object  the logo
     */
    function addLogo($logo) 
    {
        if (isset($this->logos[$logo->name])) {
            return PEAR::raiseError("logo '{$logo->name}' already defined");
        }

        $this->logos[$logo->name] = $logo;
        
        return true;
    }

    
    /**
     * Add makefile fragment
     *
     * @access public
     * @param  string
     */
    function addMakeFragment($text)
    {
        $this->makefragment[] = $text;
        return true;
    }
            

    /**
     * Add config.m4 fragment
     *
     * @access public
     * @param  string
     */
    function addConfigFragment($text, $position="top")
    {
        if (!in_array($position, array("top", "bottom"))) {
            return PEAR::raiseError("'$position' is not a valid config snippet position");
        }
        $this->configfragments[$position][] = $text;
        return true;
    }
            

    /**
     * Generate #line specs?
     *
     * @access public
     * @param  bool
     */
    function setLinespecs($state) 
    {
        $this->linespecs = $state;
    }

    // }}} 

    // {{{ output generation
        
    /**
     * Create the extensions including
     *
     * @access public
     * @param  string Directory to create (default is ./$this->name)
     */
    function createExtension($dirpath = false, $force = false) 
    {
        // default: create dir in current working directory, 
        // dirname is the extensions base name
        if (!is_string($dirpath) || $dirpath == "") {
            $dirpath = "./" . $this->name;
        } 
        
        // purge and create extension directory
        if ($dirpath !== ".") {
            if (!$force && file_exists($dirpath))  {
                return PEAR::raiseError("'$dirpath' already exists, can't create that directory (use '--force' to override)"); 
            } else if (!@System::mkdir("-p $dirpath")) {
                return PEAR::raiseError("can't create '$dirpath'");
            }
        }
        
        // make path absolute to be independant of working directory changes
        $this->dirpath = realpath($dirpath);
        
        echo "Creating '{$this->name}' extension in '$dirpath'\n";
        
        // generate complete source code
        $this->generateSource();

        // copy additional source files
        if (isset($this->packageFiles['copy'])) {
            foreach ($this->packageFiles['copy'] as $basename => $filepath) {
                copy($filepath, $this->dirpath."/".$basename);
            }
        }
        
        // generate README file
        $this->writeReadme();
        
        // generate DocBook XML documantation for PHP manual
        $manpath = $this->dirpath . "/manual/" . str_replace('_', '-', $this->name);
        if (!@System::mkdir("-p $manpath")) {
            return PEAR::raiseError("can't create '$manpath'", E_USER_ERROR);
        }
        $this->generateDocumentation($manpath);            
    }
    
    /**
     * Create the extensions code soure and project files
     *
     * @access public
     */
    function generateSource() 
    {
        // generate source and header files
        $this->writeHeaderFile();
        $this->writeCodeFile();

        foreach($this->logos as $logo) {
            $fp = fopen("{$this->dirpath}/{$logo->name}_logos.h", "w");
            fwrite($fp, CodeGen_Tools_Indent::tabify($logo->hCode()));
            fclose($fp);
        }
        
        // generate project files for configure (unices and similar platforms like cygwin)
        if ($this->platform->test("unix")) {
            $this->writeConfigM4();
        }
        
        // generate project files for Windows platform (VisualStudio/C++ V6)
        if ($this->platform->test("windows")) {
            $this->writeMsDevStudioDsp();
            $this->writeConfigW32();
        }
        
        // generate .cvsignore file entries
        $this->writeDotCvsignore();

        // generate EXPERIMENTAL file for unstable release states
        $this->writeExperimental();
        
        // generate CREDITS file
        $this->writeCredits();
        
        // generate LICENSE file if license given
        if ($this->license) {
            $this->license->writeToFile($this->dirpath."/LICENSE");
            $this->files['doc'][] = "LICENSE";
        }
        
        // generate PEAR/PECL package.xml file
        $this->writePackageXml();
        
        // generate test case templates
        $this->writeTestFiles();
    }
    
    // {{{   docbook documentation

    /**
     * Create the extension documentation DocBook XML files
     *
     * @access public
     * @param  string Directory to write to
     */
    function generateDocumentation($docdir) 
    {
        $idName = str_replace('_', '-', $this->name);
        
        $fp = fopen("$docdir/reference.xml", "w");
        fputs($fp,
"<?xml version='1.0' encoding='iso-8859-1'?>
<!-- ".'$'."Revision: 1.1 $ -->
 <reference id='ref.$idName'>
  <title>{$this->summary}</title>
  <titleabbrev>$idName</titleabbrev>

  <partintro>
   <section id='$idName.intro'>
    &reftitle.intro;
    <para>
{$this->description}
    </para>
   </section>
   
   &reference.$idName.configure;

   <section id='$idName.resources'>
    &reftitle.resources;
");

        if (empty($this->resources)) {
            fputs($fp, "   &no.resource;\n");
        } else {
            foreach ($this->resources as $resource) {
                fputs($fp, $resource->docEntry($idName));
            }
        }


        fputs($fp,
"   </section>

   <section id='$idName.constants'>
    &reftitle.constants;
");

        if (empty($this->constants)) {
            fputs($fp, "    &no.constants;\n");
        } else {
            fputs($fp, CodeGen_PECL_Element_Constant::docHeader($idName));

            foreach ($this->constants as $constant) {
                fputs($fp, $constant->docEntry($idName));
            }

            fputs($fp, CodeGen_PECL_Element_Constant::docFooter());
        }

        fputs($fp,
"   </section>
   
  </partintro>

&reference.$idName.functions;

 </reference>
");

        fputs($fp, CodeGen_PECL_Element::docEditorSettings());

        fclose($fp);
  
        // configure options and dependencies have their own file
        $fp = fopen("$docdir/configure.xml","w");

        fputs($fp,"\n   <section id='$idName.requirements'>\n    &reftitle.required;\n");
        if (empty($this->libs) && empty($this->headers)) {
            fputs($fp, "    &no.requirement;\n");
        } else {
            // TODO allow custom text
            if (isset($this->libs)) {
                $libs = array();
                foreach ($this->libs as $lib) {
                    $libs[] = $lib->getName();
                }
                $ies = count($libs)>1 ? "ies" :"y";
                fputs($fp, "<para>This extension requires the following librar$ies: ".join(",", $libs)."</para>\n");
            }
            if (isset($this->headers)) {
                $headers = array();
                foreach ($this->headers as $header) {
                    $headers[] = $header->getName();
                }
                $s = count($headers)>1 ? "s" : "";
                fputs($fp, "<para>This extension requires the following header$s: ".join(",", $headers)."</para>\n");
            }
        }
        fputs($fp, "\n   </section>\n\n");

        fputs($fp,"\n   <section id='$idName.install'>\n    &reftitle.install;\n");
        if (empty($this->with)) {
            fputs($fp, "    &no.install;\n");
        } else {
            foreach ($this->with as $with) {
                if (isset($with->summary)) {
                    if (strstr($with->summary, "<para>")) {
                        fputs($fp, $with->summary);
                    } else {
                        fputs($fp, "    <para>\n".rtrim($with->summary)."\n    </para>\n");
                    }
                } else {
                   // TODO default text
                } 
            }
        }
        fputs($fp, "\n   </section>\n\n");

        fputs($fp,"\n   <section id='$idName.configuration'>\n    &reftitle.runtime;\n");
        if (empty($this->phpini)) {
            fputs($fp, "    &no.config;\n");
        } else {
            fputs($fp, CodeGen_PECL_Element_Ini::docHeader($this->name)); 
            foreach ($this->phpini as $phpini) {
                fputs($fp, $phpini->docEntry($idName));
            }
            fputs($fp, CodeGen_PECL_Element_Ini::docFooter()); 
        }
        fputs($fp, "\n   </section>\n\n");
            
        fputs($fp, CodeGen_PECL_Element::docEditorSettings());
        fclose($fp);

        @mkdir("$docdir/functions");
        foreach ($this->functions as $name => $function) {
            $filename = $docdir . "/functions/" . strtolower(str_replace("_", "-", $name)) . ".xml";
            $funcfile = fopen($filename, "w");
            fputs($funcfile, $function->docEntry($idName));
            fclose($funcfile);
        } 
    }

    // }}} 




    // {{{   extension entry
    /**
     * Create the module entry code for this extension
     *
     * @access private
     * @return string  zend_module_entry code fragment
     */
    function generateExtensionEntry() {
        $name = $this->name;
        $upname = strtoupper($this->name);

        $code = "
/* {{{ {$name}_module_entry
 */
zend_module_entry {$name}_module_entry = {
    STANDARD_MODULE_HEADER,
    \"$name\",
    {$name}_functions,
    PHP_MINIT($name),     /* Replace with NULL if there is nothing to do at php startup   */ 
    PHP_MSHUTDOWN($name), /* Replace with NULL if there is nothing to do at php shutdown  */
    PHP_RINIT($name),     /* Replace with NULL if there is nothing to do at request start */
    PHP_RSHUTDOWN($name), /* Replace with NULL if there is nothing to do at request end   */
    PHP_MINFO($name),
    \"".$this->release->version."\", 
    STANDARD_MODULE_PROPERTIES
};
/* }}} */

";

        $code .= "#ifdef COMPILE_DL_$upname\n";
        if ($this->language == "cpp") $code .= "extern \"C\" {\n";
        $code .= "ZEND_GET_MODULE($name)\n";
        if ($this->language == "cpp") $code .= "} // extern \"C\"\n";
        $code .= "#endif\n\n";

        return $code;
    }

    // }}} 

    // {{{ globals and ini
    /**
     * Create the module globals c code fragment
     *
     * @access private
     * @return string  module globals code fragment
     */

    function generateGlobalsC() 
    {
        if (empty($this->globals)) return "";
        
        $code  = "\n/* {{{ globals and ini entries */\n";
        $code .= "ZEND_DECLARE_MODULE_GLOBALS({$this->name})\n\n";
        
        if (!empty($this->phpini)) {
            $code .= CodeGen_PECL_Element_Ini::cCodeHeader($this->name);
            foreach ($this->phpini as $phpini) {
                $code .= $phpini->cCode($this->name);
            }
            $code .= CodeGen_PECL_Element_Ini::cCodeFooter($this->name);
        }
        
        if (!empty($this->globals)) {
            $code .= CodeGen_PECL_Element_Global::cCodeHeader($this->name);
            foreach ($this->globals as $global) {
                $code .= $global->cCode($this->name);
            }
            $code .= CodeGen_PECL_Element_Global::cCodeFooter($this->name);
        }
        
        $code .= "/* }}} */\n\n";
        return $code;
    }
    
    
    
    /**
     * Create the module globals c header file fragment
     *
     * @access private
     * @return string  module globals code fragment
     */
    function generateGlobalsH() 
    {
        if (empty($this->globals)) return "";
        
        $code = CodeGen_PECL_Element_Global::hCodeHeader($this->name);
        foreach ($this->globals as $global) {
            $code .= $global->hCode($this->name);
        }
        $code .= CodeGen_PECL_Element_Global::hCodeFooter($this->name);
        
        return $code;
    }
    
    // }}} 

    // {{{
    /**
     * Create global function registration
     *
     * @access private
     * @return string  function registration code fragments
     */
    function generateFunctionRegistrations()
    {
        $code  = "/* {{{ {$this->name}_functions[] */\n";
        $code .= "function_entry {$this->name}_functions[] = {\n";
        foreach ($this->functions as $function) {
            $code .=  sprintf("    PHP_FE(%-20s, NULL)\n",$function->name);
        }
        foreach ($this->classes as $class) {
            foreach ($class->methods as $method) {
                $proceduralName = $method->getProceduralName();
                if ($proceduralName) {
                    $code .=  sprintf("    ZEND_FALIAS(%s_%s, %s, NULL)\n",
                                      $class->getName(),
                                      $method->getName().
                                      $proceduralName()
                                      );
                }
            }
        }
        $code .=  "    { NULL, NULL, NULL }\n";
        $code .=  "};\n/* }}} */\n\n";

        return $code;
    }
    // }}}
    
    // {{{
    /**
     * Create global class registration code and functions
     *
     * @access private
     * @return string  class registration code fragments
     */
    function generateClassRegistrations()
    {
        if (empty($this->classes)) return "";

        $code = "/* {{{ classes */\n";

        foreach ($this->classes as $class) {
            $code .= $class->globalCode($this);
        }

        $code .= "/* }}} */\n\n";

        return $code;
    }
    // }}}

    // {{{ license and authoers
    /**
     * Create the license part of the source file header comment
     *
     * @access private
     * @return string  code fragment
     */
    function getLicense() 
    {    
        $code = "/*\n";
        $code.= "   +----------------------------------------------------------------------+\n";
        
        if (is_object($this->license)) {
            $code.= $this->license->getComment();
        } else {
            $code.= sprintf("   | unknown license: %-52s |\n", $this->license);
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        
        foreach ($this->authors as $author) {
            $code.= $author->comment();
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        $code.= "*/\n\n";
        
        $code.= "/* $ Id: $ */ \n\n";
        
        return $code;
    }
    
    // }}} 


    // {{{ header file

    /**
     * Write the complete C header file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeHeaderFile() 
    {
        $filename = "php_{$this->name}.h";
        
        $upname = strtoupper($this->name);
        
        ob_start();
        
        echo $this->getLicense();
        echo "#ifndef PHP_{$upname}_H\n";
        echo "#define PHP_{$upname}_H\n\n";
        
        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }
        
        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        echo '
#ifdef HAVE_CONFIG_H
#include "config.h"
#endif

#include <php.h>

#ifdef HAVE_'.$upname.'

#include <php_ini.h>
#include <SAPI.h>
#include <ext/standard/info.h>

';

        echo "#ifdef  __cplusplus\n";
        echo "} // extern \"C\" \n";
        echo "#endif\n";

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }

        if (isset($this->code["header"]["top"])) {
            foreach ($this->code["header"]["top"] as $code) {
                echo CodeGen_Tools_Indent::indent(0, $code);
            }
        }

        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        echo "
extern zend_module_entry {$this->name}_module_entry;
#define phpext_{$this->name}_ptr &{$this->name}_module_entry

#ifdef PHP_WIN32
#define PHP_{$upname}_API __declspec(dllexport)
#else
#define PHP_{$upname}_API
#endif

PHP_MINIT_FUNCTION({$this->name});
PHP_MSHUTDOWN_FUNCTION({$this->name});
PHP_RINIT_FUNCTION({$this->name});
PHP_RSHUTDOWN_FUNCTION({$this->name});
PHP_MINFO_FUNCTION({$this->name});

#ifdef ZTS
#include \"TSRM.h\"
#endif

#define FREE_RESOURCE(resource) zend_list_delete(Z_LVAL_P(resource))

";

        echo $this->generateGlobalsH();

        echo "\n";

        foreach ($this->functions as $name => $function) {
            echo "PHP_FUNCTION($name);\n";
        }
            
        foreach ($this->streams as $name => $stream) {
            echo CodeGen_Tools_Indent::indent(1, $stream->hCode());
        }

        echo "#ifdef  __cplusplus\n";
        echo "} // extern \"C\" \n";
        echo "#endif\n";
        echo "\n";

        // write #defines for <constant>s
        $defines = "";
        foreach ($this->constants as $constant) {
            $defines.= $constant->hCode($this);
        }
        if ($defines !== "") {
            echo "/* mirrored PHP Constants */\n";
            echo $defines;
            echo "\n";
        } 
        
        // add bottom header snippets
        if (isset($this->code["header"]["bottom"])) {
            echo "/* 'bottom' header snippets*/\n";
            foreach ($this->code["header"]["bottom"] as $code) {
                echo CodeGen_Tools_Indent::indent(0, $code);
            }
            echo "\n";
        }

        echo "#endif /* PHP_HAVE_{$upname} */\n\n";
        echo "#endif /* PHP_{$upname}_H */\n\n";

        echo CodeGen_PECL_Element::cCodeEditorSettings();

        $this->addPackageFile('header', $filename); 

        return $this->obToFile($filename);
    }

    // }}} 

    // {{{ internal functions

    /**
     * Create code for the internal functions like MINIT etc ...
     *
     * @access private
     * @return string  code snippet
     */
    
    function internalFunctionsC() 
    {
        $need_block = false;
        
        $code = "
/* {{{ PHP_MINIT_FUNCTION */
PHP_MINIT_FUNCTION({$this->name})
{
";

        if (count($this->globals)) {
            $code .= "    ZEND_INIT_MODULE_GLOBALS({$this->name}, php_{$this->name}_init_globals, php_{$this->name}_shutdown_globals)\n";
            $need_block = true;
        }

        if (count($this->phpini)) {
            $code .= "    REGISTER_INI_ENTRIES();\n";
            $need_block = true;
        }
           
        foreach ($this->logos as $logo) {
            $code .= CodeGen_Tools_Indent::indent(4, $logo->minitCode());
            $need_block = true;
        }
            
        if (count($this->constants)) {
            foreach ($this->constants as $constant) {
                $code .= CodeGen_Tools_Indent::indent(4, $constant->cCode($this->name));
            }
            $need_block = true;
        }
            
        if (count($this->resources)) {
            foreach ($this->resources as $resource) {
                $code .= CodeGen_Tools_Indent::indent(4, $resource->minitCode());
            }
            $need_block = true;         
        }

        if (count($this->classes)) {
          foreach ($this->classes as $class) {
            $code .= CodeGen_Tools_Indent::indent(4, $class->minitCode($this));
          }
          $need_block = true;
        }
            
        if (count($this->streams)) {
          foreach ($this->streams as $stream) {
            $code .= CodeGen_Tools_Indent::indent(4, $stream->minitCode($this));
          }
          $need_block = true;
        }
            
        if (isset($this->internalFunctions['MINIT'])) {
            if ($need_block) $code .= "\n    {\n";
            $code .= CodeGen_Tools_Indent::indent(8, $this->internalFunctions['MINIT']->code);
            if ($need_block) $code .= "\n    }\n";
        } else {
            $code .="\n    /* add your stuff here */\n";
        }
        $code .= "
    return SUCCESS;
}
/* }}} */

";
            
        $code .= "
/* {{{ PHP_MSHUTDOWN_FUNCTION */
PHP_MSHUTDOWN_FUNCTION({$this->name})
{
";
            
        if (count($this->phpini)) {
            $code .= "    UNREGISTER_INI_ENTRIES();\n";
        }

        foreach ($this->logos as $logo) {
            $code .= CodeGen_Tools_Indent::indent(4, $logo->mshutdownCode());
            $need_block = true;
        }
            
        if (isset($this->internalFunctions['MSHUTDOWN'])) {
            if (count($this->phpini)) $code .= "\n    {\n";
            $code .= CodeGen_Tools_Indent::indent(4, $this->internalFunctions['MSHUTDOWN']->code);
            if (count($this->phpini)) $code .= "\n    }\n";
        } else {
            $code .="\n    /* add your stuff here */\n";
        }

        $code .= "
  
    return SUCCESS;
}
/* }}} */

";
        
        $code .= "
/* {{{ PHP_RINIT_FUNCTION */
PHP_RINIT_FUNCTION({$this->name})
{
";

        if (isset($this->internalFunctions['RINIT'])) {
            $code .= CodeGen_Tools_Indent::indent(4, $this->internalFunctions['RINIT']->code);
        } else {
          $code .= "    /* add your stuff here */\n";
        }

        $code .= "
    return SUCCESS;
}
/* }}} */

";

        $code .= "
/* {{{ PHP_RSHUTDOWN_FUNCTION */
PHP_RSHUTDOWN_FUNCTION({$this->name})
{
";

        if (isset($this->internalFunctions['RSHUTDOWN'])) {
            $code .= CodeGen_Tools_Indent::indent(4, $this->internalFunctions['RSHUTDOWN']->code);
        } else {
            $code .= "    /* add your stuff here */\n";
        }

        $code .= "
    return SUCCESS;
}
/* }}} */

";
    
        $code .= "
/* {{{ PHP_MINFO_FUNCTION */
PHP_MINFO_FUNCTION({$this->name})
{
    php_info_print_box_start(0);
";

        foreach ($this->logos as $logo)
        {
            $code.= "
    php_printf(\"<img src='\");
    if (SG(request_info).request_uri) {
        php_printf(\"%s\", (SG(request_info).request_uri));
    }   
    php_printf(\"?=%s\", ".($logo->id).");
    php_printf(\"' align='right' alt='image' border='0'>\\n\");

"; 
        }

        if (isset($this->summary)) {
            $code .= "    php_printf(\"<p>".str_replace('"','\\"',$this->summary)."</p>\\n\");\n";
        }
        if (isset($this->release)) {
            $code .= "    php_printf(\"<p>Version {$this->release->version}{$this->release->state} ({$this->release->date})</p>\\n\");\n";
        }

        if (count($this->authors)) {
            $code .= "    php_printf(\"<p><b>Authors:</b></p>\\n\");\n";
            foreach ($this->authors as $author) {
                $code.= CodeGen_Tools_Indent::indent(4, $author->phpinfo());
            }
        }

        $code.=
"    php_info_print_box_end();
";

        if (isset($this->internalFunctions['MINFO'])) {
            $code .= "\n    {\n";
            $code .= $this->internalFunctions['MINFO']->code;
            $code .= "\n    }\n";
        } else {
            $code .= "    /* add your stuff here */\n";
        }


        if (count($this->phpini)) {
            $code .= "\n    DISPLAY_INI_ENTRIES();";
        }
        $code .= "
}
/* }}} */

";

        return $code;
    }

    // }}} 

    // {{{ public functions
    /**
     * Create code for the exported PHP functions
     *
     * @access private
     * @return string  code snippet
     */
    function publicFunctionsC() {
        $code = "";

        foreach ($this->functions as $function) {
            $code .= $function->cCode(&$this);
        }
        
        return $code;
    }

    // }}} 


  // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeCodeFile() {
        $filename = "{$this->name}.{$this->language}";  // todo extension logic

        $upname = strtoupper($this->name);

        ob_start();
            
        echo $this->getLicense();

        echo "#include \"php_{$this->name}.h\"\n\n";
            
        echo "#if HAVE_$upname\n\n";
  
        if (isset($this->code["code"]["top"])) {
            foreach ($this->code["code"]["top"] as $code) {
                echo CodeGen_Tools_Indent::indent(0, $code);
            }
        }

        foreach ($this->logos as $logo) {
            echo $logo->cCode($this->name);
        }

        if (!empty($this->resources)) {
            foreach ($this->resources as $resource) {
                echo $resource->cCode($this);
            }
        }

        echo $this->generateClassRegistrations();

        echo $this->generateFunctionRegistrations();
            
        echo $this->generateExtensionEntry();
 
        echo $this->generateGlobalsC();

        echo $this->internalFunctionsC();
 
        echo $this->publicFunctionsC();

        if (isset($this->code["code"]["bottom"])) {
            foreach ($this->code["code"]["bottom"] as $code) {
                echo CodeGen_Tools_Indent::indent(0, $code);
            }
        }

        echo "#endif /* HAVE_$upname */\n\n";
  
        echo CodeGen_PECL_Element::cCodeEditorSettings();
 
        $this->addPackageFile('code', $filename); 

        return $this->obToFile($filename, $this->OB_TABIFY);
    }

    // }}} 


    // {{{ config.m4 file

    /**
     * Write config.m4 file for autoconf 
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeConfigM4() {
        $upname = strtoupper($this->name);

        $flagVar = ($this->language === "cpp") ? "CXXFLAGS" : "CFLAGS";

        ob_start();

        echo 
'dnl
dnl $ Id: $
dnl
';

        
        if (isset($this->with[$this->name])) {
            $with = $this->with[$this->name];
            echo " 
PHP_ARG_WITH({$with->name}, whether to enable {$with->name} functions,
[  --with-{$with->name}[=DIR]      With {$with->name} support])
\n";
        } else {
            echo "
PHP_ARG_ENABLE({$this->name}, whether to enable {$this->name} functions,
[  --enable-{$this->name}         Enable {$this->name} support])
";
        }

        echo "\n";

        echo "if test \"\$PHP_$upname\" != \"no\"; then\n";

        foreach ($this->configfragments['top'] as $fragment) {
            echo "$fragment\n";
        }


        foreach ($this->with as $with) {
            if ($with->name != $this->name) {
                echo " 
PHP_ARG_WITH({$with->name}, {$with->summary},
[  --with-{$with->name}[=DIR]      With {$with->name} support])
\n";
            }

            $withUpname = strtoupper($with->name);

            echo "
  if test -r \"\$PHP_$withUpname/{$with->testfile}\"; then
    PHP_{$withUpname}_DIR=\"\$PHP_$withUpname\"
  else
    AC_MSG_CHECKING(for {$with->name} in default path)
    for i in ".str_replace(":"," ",$with->defaults)."; do
      if test -r \"\$i/{$with->testfile}\"; then
        PHP_{$withUpname}_DIR=\$i
        AC_MSG_RESULT(found in \$i)
        break
      fi
    done
    if test \"x\" = \"x\$PHP_{$withUpname}_DIR\"; then
      AC_MSG_ERROR(not found)
    fi
  fi

";

            $pathes = array();
            foreach($with->headers as $header) {
               $pathes[$header->getPath()] = true; // TODO WTF???
            }
       
            foreach (array_keys($pathes) as $path) {
                echo "  PHP_ADD_INCLUDE(\$PHP_{$withUpname}_DIR/$path)\n";
            }


            echo "  export OLD_$flagVar=\"\$$flagVar\"\n";

            echo "  export $flagVar=\"\$$flagVar \$INCLUDES -DHAVE_$withUpname\"\n";

            foreach($with->headers as $header) {
                echo $header->configm4($this->name, $this->name);
            }  

            foreach ($with->libs as $lib) {
                echo $lib->configm4($this->name, $with->name);
            }
            
            echo "  export $flagVar=\"\$OLD_$flagVar\"\n";
        }

        $pathes = array();
        foreach($this->headers as $header) {
           $pathes[$header->getPath()] = true; // TODO WTF???
        }
       
        foreach (array_keys($pathes) as $path) {
            echo "  PHP_ADD_INCLUDE(\$PHP_{$upname}_DIR/$path)\n";
        }

        if ($this->language === "cpp") {
            echo "  PHP_REQUIRE_CXX\n";
            echo "  AC_LANG_CPLUSPLUS\n";
            echo "  PHP_ADD_LIBRARY(stdc++,,{$upname}_SHARED_LIBADD)\n";
        }

        echo "  export OLD_$flagVar=\"\$$flagVar\"\n";

        echo "  export $flagVar=\"\$$flagVar \$INCLUDES -DHAVE_".strtoupper($this->name)."\"\n";

        foreach($this->headers as $header) {
            echo $header->configm4($this->name, $this->name);
        }  

        foreach ($this->resources as $resource) {
            echo "  AC_CHECK_TYPE({$resource->payload}, [], [AC_MSG_ERROR(required payload type for resource {$resource->name} not found)], [#include \"php_{$this->name}.h\"])\n";
        }

        echo "  export $flagVar=\"\$OLD_$flagVar\"\n";

        if (count($this->libs)) {
            $first = true;

            foreach ($this->libs as $lib) {
                echo $lib->configm4($this->name, $this->name);
            }
        }

        echo "\n";


        echo "
  PHP_SUBST({$upname}_SHARED_LIBADD)
  AC_DEFINE(HAVE_$upname, 1, [ ])
  PHP_NEW_EXTENSION({$this->name}, ".join(" ", array_keys($this->packageFiles['code']))." , \$ext_shared)
";

        if (count($this->makefragment)) {
            echo "  PHP_ADD_MAKEFILE_FRAGMENT\n";

            $frag = fopen($this->dirpath."/Makefile.frag","w");
            foreach($this->makefragment as $block) {
                fputs($frag, CodeGen_Tools_Indent::tabify("\n$block\n"));
            }
            fclose($frag);
        }

        foreach ($this->configfragments['bottom'] as $fragment) {
            echo "$fragment\n";
        }

        echo 
"
fi

";

        $this->addPackageFile("conf", "config.m4");

        return $this->obToFile("config.m4", $this->OB_TABIFY);
    }

    // }}} 


    // {{{ config.w32 file

    /**
     * Write config.w32 file for new windows build system
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeConfigW32() {
        // TODO fragments
        $upname = strtoupper($this->name);

        ob_start();

        echo 
'// $ Id: $
// vim:ft=javascript
';

        if (isset($this->with[$this->name])) {
            echo "
ARG_WITH('{$this->name}', '{$this->summary}', 'no');

";
        } else {
            echo "
ARG_ENABLE('{$this->name}' , '{$this->summary}', 'no');
";
        }

        echo "if (PHP_$upname == \"yes\") {\n";

        // add libraries from <deps> section
        foreach ($this->libs as $lib) {
            echo $lib->configw32($this->name, $this->name);
        }

        foreach($this->headers as $header) {
            echo $header->configw32($this->name, $this->name);
        }

        echo "  EXTENSION(\"{$this->name}\", \"".join(" ", array_keys($this->packageFiles['code']))."\");\n";

        echo "  AC_DEFINE(\"HAVE_$upname\", 1, \"{$this->name} support\");\n";

        echo "}\n";

        $this->addPackageFile("conf", "config.w32");

        return $this->obToFile("config.w32", $this->OB_UNTABIFY | $this->OB_DOSIFY);
    }

    // }}} 

    // {{{ M$ dev studio project file
    /**
     * Write project file for VisualStudio V6
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeMsDevStudioDsp() 
    {
        ob_start();

        // these system libraries are always needed?
        // (list taken from sample *.dsp files in php ext tree...) 
        $winlibs = "kernel32.lib user32.lib gdi32.lib winspool.lib comdlg32.lib advapi32.lib ";
        $winlibs.= "shell32.lib ole32.lib oleaut32.lib uuid.lib odbc32.lib odbccp32.lib";

        // add libraries from <deps> section
        if (count($this->libs)) {
            foreach ($this->libs as $lib) {
                if (!$lib["platform"]->test("windows")) {
                    continue;
                }
                $winlibs .= " $lib[name].lib";
            }
        }


        echo
'# Microsoft Developer Studio Project File - Name="'.$this->name.'" - Package Owner=<4>
# Microsoft Developer Studio Generated Build File, Format Version 6.00
# ** DO NOT EDIT **

# TARGTYPE "Win32 (x86) Dynamic-Link Library" 0x0102

CFG='.$this->name.' - Win32 Debug_TS
!MESSAGE This is not a valid makefile. To build this project using NMAKE,
!MESSAGE use the Export Makefile command and run
!MESSAGE 
!MESSAGE NMAKE /f "'.$this->name.'.mak".
!MESSAGE 
!MESSAGE You can specify a configuration when running NMAKE
!MESSAGE by defining the macro CFG on the command line. For example:
!MESSAGE 
!MESSAGE NMAKE /f "'.$this->name.'.mak" CFG="'.$this->name.' - Win32 Debug_TS"
!MESSAGE 
!MESSAGE Possible choices for configuration are:
!MESSAGE 
!MESSAGE "'.$this->name.' - Win32 Release_TS" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE "'.$this->name.' - Win32 Debug_TS" (based on "Win32 (x86) Dynamic-Link Library")
!MESSAGE 

# Begin Project
# PROP AllowPerConfigDependencies 0
# PROP Scc_ProjName ""
# PROP Scc_LocalPath ""
CPP=cl.exe
MTL=midl.exe
RSC=rc.exe

!IF  "$(CFG)" == "'.$this->name.' - Win32 Release_TS"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 0
# PROP BASE Output_Dir "Release_TS"
# PROP BASE Intermediate_Dir "Release_TS"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 0
# PROP Output_Dir "Release_TS"
# PROP Intermediate_Dir "Release_TS"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MT /W3 /GX /O2 /D "WIN32" /D "NDEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "'.strtoupper($this->name).'_EXPORTS" /YX /FD /c
# ADD CPP /nologo /MT /W3 /GX /O2 /I "..\.." /I "..\..\Zend" /I "..\..\TSRM" /I "..\..\main" /D "WIN32" /D "PHP_EXPORTS" /D "COMPILE_DL_'.strtoupper($this->name).'" /D ZTS=1 /D HAVE_'.strtoupper($this->name).'=1 /D ZEND_DEBUG=0 /D "NDEBUG" /D "_WINDOWS" /D "ZEND_WIN32" /D "PHP_WIN32" /YX /FD /c
# ADD BASE MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "NDEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x407 /d "NDEBUG"
# ADD RSC /l 0x407 /d "NDEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 '.$winlibs.' /nologo /dll /machine:I386
# ADD LINK32 php4ts.lib '.$winlibs.' /nologo /dll /machine:I386 /out:"..\..\Release_TS\php_'.$this->name.'.dll" /libpath:"..\..\Release_TS" /libpath:"..\..\Release_TS_Inline"

!ELSEIF  "$(CFG)" == "'.$this->name.' - Win32 Debug_TS"

# PROP BASE Use_MFC 0
# PROP BASE Use_Debug_Libraries 1
# PROP BASE Output_Dir "Debug_TS"
# PROP BASE Intermediate_Dir "Debug_TS"
# PROP BASE Target_Dir ""
# PROP Use_MFC 0
# PROP Use_Debug_Libraries 1
# PROP Output_Dir "Debug_TS"
# PROP Intermediate_Dir "Debug_TS"
# PROP Ignore_Export_Lib 0
# PROP Target_Dir ""
# ADD BASE CPP /nologo /MTd /W3 /Gm /GX /ZI /Od /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "_MBCS" /D "_USRDLL" /D "'.strtoupper($this->name).'_EXPORTS" /YX /FD /GZ  /c
# ADD CPP /nologo /MTd /W3 /Gm /GX /ZI /Od /I "..\.." /I "..\..\Zend" /I "..\..\TSRM" /I "..\..\main" /D ZEND_DEBUG=1 /D "WIN32" /D "_DEBUG" /D "_WINDOWS" /D "PHP_EXPORTS" /D "COMPILE_DL_'.strtoupper($this->name).'" /D ZTS=1 /D "ZEND_WIN32" /D "PHP_WIN32" /D HAVE_'.strtoupper($this->name).'=1 /YX /FD /GZ  /c
# ADD BASE MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD MTL /nologo /D "_DEBUG" /mktyplib203 /win32
# ADD BASE RSC /l 0x407 /d "_DEBUG"
# ADD RSC /l 0x407 /d "_DEBUG"
BSC32=bscmake.exe
# ADD BASE BSC32 /nologo
# ADD BSC32 /nologo
LINK32=link.exe
# ADD BASE LINK32 '.$winlibs.' /nologo /dll /debug /machine:I386 /pdbtype:sept
# ADD LINK32 php4ts_debug.lib '.$winlibs.' /nologo /dll /debug /machine:I386 /out:"..\..\Debug_TS\php_'.$this->name.'.dll" /pdbtype:sept /libpath:"..\..\Debug_TS"

!ENDIF 

# Begin Target

# Name "'.$this->name.' - Win32 Release_TS"
# Name "'.$this->name.' - Win32 Debug_TS"
';


        echo '
# Begin Group "Source Files"

# PROP Default_Filter "cpp;c;cxx;rc;def;r;odl;idl;hpj;bat"
';

        foreach ($this->packageFiles['code'] as $basename => $filepath) {
             $filename = "./$basename";

             echo "
# Begin Source File

SOURCE=$filename
# End Source File
";
        }

        echo '
# End Group
';




        echo '
# Begin Group "Header Files"

# PROP Default_Filter "h;hpp;hxx;hm;inl"
';

        foreach ($this->packageFiles['header'] as $filename) {
            if ($filename{0}!='/' && $filename{0}!='.') {
                $filename = "./$filename";
            }
            $filename = str_replace("/","\\",$filename);

            echo "
# Begin Source File

SOURCE=$filename
# End Source File
";
        }

        echo
'# End Group
# End Target
# End Project
';

        $filename = $this->name.".dsp"; 
        $this->addPackageFile("conf", $filename);

        return $this->obToFile($filename, $this->OB_UNTABIFY | $this->OB_DOSIFY);
    }

// }}} 




    /**
     * Write authors to the CREDITS file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writeCredits() 
    {
        if (count($this->authors)) {
            $this->addPackageFile("doc", "CREDITS");
            $fp = fopen($this->dirpath."/CREDITS", "w");
            fputs($fp, "{$this->name}\n");
            $names = array();
            foreach($this->authors as $author) {
                $names[] = $author->name;
            }
            fputs($fp, join(", ", $names) . "\n"); 
            fclose($fp);
        }
    }


    /**
    * Write EXPERIMENTAL file for non-stable extensions
    *
    * @access private
    * @param  string  directory to write to
    */
    function writeExperimental() 
    {
        if (($this->release) && isset($this->release->state) && $this->release->state !== 'stable') {
            $this->addPackageFile("doc", "EXPERIMENTAL");
            $fp = fopen($this->dirpath."/EXPERIMENTAL", "w");
            fputs($fp,
"this extension is experimental,
its functions may change their names 
or move to extension all together 
so do not rely to much on them 
you have been warned!
");
            fclose($fp);
        }
    }

    /**
     * Write PEAR/PECL package.xml file
     *
     * @access private
     * @param  string  directory to write to
     */
    function writePackageXml() 
    {
        ob_start();
        
        echo 
"<?xml version=\"1.0\" encoding=\"iso-8859-1\"?>
<!DOCTYPE package SYSTEM \"http://pear.php.net/dtd/package-1.0\">
<package>
  <name>{$this->name}</name>
";

        if (isset($this->summary)) {
            echo "  <summary>{$this->summary}</summary>\n";
        }

        if (isset($this->description)) {
            echo "  <description>\n".rtrim($this->description)."\n  </description>\n";
        }
        
        if ($this->license) {
            echo "  <license>".$this->license->shortname."</license>\n";
        }

        foreach ($this->with as $with) {
            $configOption = "--with-".$with->name;
            echo "  <configureoptions>\n";
            echo "   <configureoption name=\"{$configOption}\" default=\"autodetect\" prompt=\"{$with->name} installation directory?\" />\n";
            echo "  </configureoptions>\n";
        }

        if (count($this->authors)) {
            echo "\n  <maintainers>\n";
            foreach ($this->authors as $author) {
                echo $author->packageXml();
            }
            echo "  </maintainers>\n";
        }
        
        if (isset($this->release)) {
            echo $this->release->packageXml();
        }

// TODO dependencies

        echo "\n  <filelist>\n";
        echo "    <dir role=\"doc\" name=\"/\">\n";
        if (@is_array($this->packageFiles['doc'])) {
            foreach ($this->packageFiles['doc'] as $file) {
                echo "      <file role=\"doc\">$file</file>\n";
            }
        }

        foreach (array("conf", "code", "header") as $type) { 
            foreach ($this->packageFiles[$type] as $basename => $filepath) {
                echo "      <file role=\"src\">$basename</file>\n";
            }
        }
        
        echo "    </dir>\n";
        echo "  </filelist>\n";


        echo "</package>\n";
        
        return $this->obToFile("package.xml");
    }

    // }}}

    /**
     * add a custom test case
     *
     * @access public
     * @param  object  a Test object
     */
    function addTest(CodeGen_PECL_Element_Test $test) {
        $this->testcases[$test->name] = $test;
        return true;
    }

    /**
     * Write test case files
     *
     * @access private
     */
    function writeTestFiles() {
        @mkdir($this->dirpath."/tests");
    
        // function related tests
        foreach ($this->functions as $function) {
            $function->writeTest($this);
        }

        // custom test cases (may overwrite custom function test cases)
        foreach ($this->testcases as $test) {
            $test->writeTest($this);
        }
    }

    /**
    * Write .cvsignore entries
    *
    * @access public
    * @param  string  directory to write to
    */
    function writeDotCvsignore()
    {
        // open output file
        $fp = fopen($this->dirpath."/.cvsignore", "w");

        // unix specific entries
        if ($this->platform->test("unix")) {
            fputs($fp, "*.lo\n");
            fputs($fp, "*.la\n");
            fputs($fp, ".deps\n");
            fputs($fp, ".libs\n");
            fputs($fp, "Makefile\n");
            fputs($fp, "Makefile.fragments\n");
            fputs($fp, "Makefile.global\n");
            fputs($fp, "Makefile.objects\n");
            fputs($fp, "acinclude.m4\n");
            fputs($fp, "aclocal.m4\n");
            fputs($fp, "autom4te.cache\n");
            fputs($fp, "build\n");
            fputs($fp, "config.cache\n");
            fputs($fp, "config.guess\n");
            fputs($fp, "config.h\n");
            fputs($fp, "config.h.in\n");
            fputs($fp, "config.log\n");
            fputs($fp, "config.nice\n");
            fputs($fp, "config.status\n");
            fputs($fp, "config.sub\n");
            fputs($fp, "configure\n");
            fputs($fp, "configure.in\n");
            fputs($fp, "conftest\n");
            fputs($fp, "conftest.c\n");
            fputs($fp, "include\n");
            fputs($fp, "install-sh\n");
            fputs($fp, "libtool\n");
            fputs($fp, "ltmain.sh\n");
            fputs($fp, "missing\n");
            fputs($fp, "mkinstalldirs\n");
            fputs($fp, "modules\n");
            fputs($fp, "scan_makefile_in.awk\n");
        }

        // windows specific entries
        if ($this->platform->test("windows")) {
            fputs($fp, "*.dsw\n");
            fputs($fp, "*.plg\n");
            fputs($fp, "*.opt\n");
            fputs($fp, "*.ncb\n");
            fputs($fp, "Release\n");
            fputs($fp, "Release_inline\n");
            fputs($fp, "Debug\n");
            fputs($fp, "Release_TS\n");
            fputs($fp, "Release_TSDbg\n");
            fputs($fp, "Release_TS_inline\n");
            fputs($fp, "Debug_TS\n");
        }

        // "pear package" creates .tgz
        fputs($fp, "*.tgz\n");

        // some typical backup file patterns
        fputs($fp, "*~\n");
        fputs($fp, "#*#\n");
        fputs($fp, ".#*\n");

        fclose($fp);
    }

    /**
    * Describe next steps after successfull extension creation
    *
    * @access private
    * @param  string  directory where extension was build
    */
    function successMsg()
    {
        $msg = "Your extension has been created in directory {$this->dirpath}.\n";
        if (!isset($this->readme)) {
            $msg.= "See ".basename($this->dirpath)."/README for further instructions.\n";
        }

        return $msg;
    }


    /** 
    * Generate README file (custom or default)
    *
    * @access private
    * @param  string  directory to write to
    */
    function writeReadme() 
    {
        $configOption = isset($this->with[$this->name]) ? "--with-" : "--enable-";
        $configOption.= $this->name;

        ob_start();
?>
This is a standalone PHP extension created using CodeGen_PECL <?php echo self::version(); ?>


HACKING
=======

There are two ways to modify an extension created using CodeGen_PECL:

1) you can modify the generated code as with any other PHP extension
  
2) you can add custom code to the CodeGen_PECL XML source and re-run pecl-gen

The 2nd approach may look a bit complicated but you have be aware that any
manual changes to the generated code will be lost if you ever change the
XML specs and re-run PECL-Gen. All changes done before have to be applied
to the newly generated code again.
Adding code snippets to the XML source itself on the other hand may be a 
bit more complicated but this way your custom code will always be in the
generated code no matter how often you rerun CodeGen_PECL.

<?php if ($this->platform->test("unix")): ?>

BUILDING ON UNIX etc.
=====================

To compile your new extension, you will have to execute the following steps:

1.  $ ./phpize
2.  $ ./configure [<?php echo $configOption; ?>]
3.  $ make
[4. $ make test ] # NOTE: this doesn't work right now *)
5.  $ [sudo] make install

*) this is a general problem with "make test" and standalone extensions
   (that is being worked on) so please don't blame CodeGen_PECL for this
   
<?php endif; ?>

<?php if ($this->platform->test("windows")): ?>

BUILDING ON WINDOWS
===================

The extension provides the VisualStudio V6 project file 

  <?php echo $this->name.".dsp" ?>

To compile the extension you open this file using VisualStudio,
select the apropriate configuration for your installation
(either "Release_TS" or "Debug_TS") and create "php_<?php echo $this->name; ?>.dll"

After successfull compilation you have to copy the newly
created "php_<?php echo $this->name; ?>.dll" to the PHP
extension directory (default: C:\PHP\extensions).

<?php endif; ?>

TESTING
=======

You can now load the extension using a php.ini directive

  extension="php_<?php echo $this->name; ?>.[so|dll]"

or load it at runtime using the dl() function

  dl("php_<?php echo $this->name; ?>.[so|dll]");

The extension should now be available, you can test this
using the extension_loaded() function:

  if (extension_loaded(<?php echo $this->name; ?>))
    echo "<?php echo $this->name; ?> loaded :)";
  else
    echo "something is wrong :(";

The extension will also add its own block to the output
of phpinfo();

<?php


        return $this->obToFile("README");
    }


    var $OB_DOSIFY   = 1;
    var $OB_TABIFY   = 2;
    var $OB_UNTABIFY = 4;

    /**
     * write current output buffer to file
     *
     * @param  file path
     * @param  formating flags
     * @access private
     */
    function obToFile($path, $flags = 0)                                
    {
        $fp = fopen($this->dirpath . "/" . $path, "w");
        if (!is_resource($fp)) {
            return PEAR::raiseError("can't write to output file '$path");
        }

        $text = ob_get_clean();

        if ($flags && $this->OB_TABIFY) {
          $text = CodeGen_Tools_Indent::tabify($text);
        } else if ($flags && $this->OB_UNTABIFY) {
          $text = CodeGen_Tools_Indent::untabify($text);
        }

        if ($flags && $this->OB_DOSIFY) {
          $text = CodeGen_Tools_Indent::tabify($text);
        }

        fputs($fp, $text);
        fclose($fp);

        return true;
    }
}   


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
