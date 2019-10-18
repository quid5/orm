<?php
declare(strict_types=1);

/*
 * This file is part of the QuidPHP package.
 * Website: https://quidphp.com
 * License: https://github.com/quidphp/orm/blob/master/LICENSE
 */

namespace Quid\Orm;
use Quid\Base;
use Quid\Main;

// db
// class used to query the database and to link the results to the different ORM components
class Db extends Pdo implements \ArrayAccess, \Countable, \Iterator
{
    // trait
    use Main\_arrObj;


    // config
    public static $config = [
        'priorityIncrement'=>10, // incrémentation de la priorité lors de la création des tables et des colonnes
        'option'=>[ // tableau d'options
            'permission'=>true, // la permission est vérifié avant la requête
            'autoSave'=>false, // active ou désactive le autoSave au closeDown
            'log'=>true, // si les requêtes sont log
            'revert'=>null, // permet de conserver une clé à revert après une requête
            'logClass'=>[ // classe à utiliser pour logger ces différents types de requêtes
                'select'=>null,
                'show'=>null,
                'insert'=>null,
                'update'=>null,
                'delete'=>null,
                'create'=>null,
                'alter'=>null,
                'truncate'=>null,
                'drop'=>null],
            'classe'=>[ // option pour l'objet classe
                'default'=>[], // classe par défaut
                'colGroup'=>[], // classe pour colonne selon le group
                'colAttr'=>[]], // classe pour colonne selon un attribut
            'classeClosure'=>null, // possible de mettre uen closure comme classe (permet de gérer la cache dans boot)
            'schemaClosure'=>null, // possible de mettre une closure comme schema (permet de gérer la cache dans boot)
            'tables'=>[], // paramètre par défaut pour les tables
            'cols'=>[]], // paramètre par défaut pour les colonnes
        'output'=>[
            'all'=>[ // configuration des output spécifique à db
                'row'=>['onlySelect'=>true,'selectLimit'=>1],
                'rowRefresh'=>['onlySelect'=>true,'selectLimit'=>1],
                'rowIn'=>['onlySelect'=>true,'selectLimit'=>1],
                'rowInRefresh'=>['onlySelect'=>true,'selectLimit'=>1],
                'rowOut'=>['onlySelect'=>true,'selectLimit'=>1],
                'rows'=>['onlySelect'=>true],
                'rowsRefresh'=>['onlySelect'=>true],
                'rowsIn'=>['onlySelect'=>true],
                'rowsInRefresh'=>['onlySelect'=>true],
                'rowsOut'=>['onlySelect'=>true]],
            'row'=>['row','rowRefresh','rowIn','rowInRefresh','rowOut','rows','rowsRefresh','rowsIn','rowsInRefresh','rowsOut']] // liste des méthodes en lien avec row/rows
    ];


    // dynamique
    protected $classe = null; // propriété qui contient l'objet classe
    protected $schema = null; // propriété qui contient l'objet schema
    protected $tables = null; // propriété qui contient l'objet tables
    protected $lang = null; // propriété qui contient l'objet lang
    protected $com = null; // propriété qui contient l'objet com
    protected $role = null; // propriété qui contient l'objet role
    protected $exception = null; // propriété qui conserve la classe d'exception à utiliser
    protected $permission = [ // permissions racine de la base de donnée, les permissions des tables peuvent seulement mettre false des valeurs true, pas l'inverse
        'select'=>true,
        'show'=>true,
        'insert'=>true,
        'update'=>true,
        'delete'=>true,
        'create'=>true,
        'alter'=>true,
        'truncate'=>true,
        'drop'=>true];


    // construct
    // construction de la classe
    public function __construct(string $dsn,string $username,string $password,Main\Extenders $extenders,Main\Role $role,?array $option=null)
    {
        $this->option($option);
        $this->setDsn($dsn);
        $this->setRole($role);
        $this->connect($username,$password,$extenders);

        return;
    }


    // onBeforeMakeStatement
    // callback avant la création du statement dans makeStatement
    // méthode protégé
    protected function onBeforeMakeStatement(array $value):parent
    {
        if($this->getOption('permission') === true && !empty($value['type']))
        {
            if(!empty($value['table']) && !empty($this->tables))
            {
                if(!$this->hasPermission($value['type'],$value['table']))
                static::throw($value['type'],$value['table'],'notAllowed');
            }

            elseif(!$this->hasPermission($value['type']))
            static::throw($value['type'],'notAllowed');
        }

        return $this;
    }


    // onAfterMakeStatement
    // callback après la création du statement dans makeStatement
    // méthode protégé
    protected function onAfterMakeStatement(array $value,\PdoStatement $statement):parent
    {
        if(!empty($value['type']))
        {
            parent::onAfterMakeStatement($value,$statement);

            if($this->getOption('log') === true)
            {
                $log = $this->getOption('logClass/'.$value['type']);
                if(!empty($log))
                {
                    $go = false;

                    if(!empty($value['table']))
                    {
                        $table = $this->table($value['table']);
                        if($table->shouldLogSql($value['type']))
                        $go = true;
                    }

                    else
                    $go = true;

                    if($go === true)
                    $log::logOnCloseDown($value['type'],$value);
                }
            }
        }

        return $this;
    }


    // onCloseDown
    // méthode appelé à la fermeture
    // permet de sauvegarder toutes les lignes avec des changements non sauvegardés
    // méthode publique, car envoyé dans base/response
    public function onCloseDown():self
    {
        if($this->getOption('autoSave') === true)
        $this->autoSave();

        return $this;
    }


    // connect
    // connect à une base de donnée
    public function connect(string $username,string $password,...$args):parent
    {
        parent::connect($username,$password);
        $this->setInst();
        $this->makeSchema();
        $this->makeTables(...$args);

        return $this;
    }


    // disconnect
    // deconnect d'une base de donnée
    public function disconnect():parent
    {
        parent::disconnect();
        $this->unsetInst();
        $this->classe = null;
        $this->schema = null;
        $this->tables = null;
        $this->lang = null;
        $this->role = null;

        return $this;
    }


    // arr
    // retourne le tableau pour le trait ArrObj
    // ce n'est pas une référence, offset set et unset sont désactivés
    protected function arr():array
    {
        return $this->tables()->toArray();
    }


    // offsetGet
    // arrayAccess offsetGet retourne une table
    // lance une exception si table non existante
    public function offsetGet($key)
    {
        return $this->table($key);
    }


    // offsetSet
    // arrayAccess offsetSet n'est pas permis pour la classe
    public function offsetSet($key,$value):void
    {
        static::throw('arrayAccess','notAllowed');

        return;
    }


    // offsetUnset
    // arrayAccess offsetUnset n'est pas permis pour la classe
    public function offsetUnset($key):void
    {
        static::throw('arrayAccess','notAllowed');

        return;
    }


    // off
    // met log et rollback false
    // conserve la valeur de log et rollback dans l'option revert
    public function off():self
    {
        $log = $this->getOption('log');
        $rollback = $this->getOption('rollback');
        $this->setOption('log',false);
        $this->setOption('rollback',false);
        $this->setOption('revert',['log'=>$log,'rollback'=>$rollback]);

        return $this;
    }


    // on
    // remet log et rollback à la dernière valeur conservé dans option/revert
    public function on():self
    {
        $revert = $this->getOption('revert');
        if(is_array($revert) && array_key_exists('log',$revert) && array_key_exists('rollback',$revert))
        {
            $this->setOption('log',$revert['log']);
            $this->setOption('rollback',$revert['rollback']);
        }
        $this->setOption('revert',null);

        return $this;
    }


    // hasPermission
    // retourne vrai si la db a la permission pour le type de requête
    // si vrai, peut chercher dans l'objet table pour une permission supplémentaire, envoie une exception si la table n'existe pas
    public function hasPermission(string $type,$table=null):bool
    {
        $return = true;

        if(array_key_exists($type,$this->permission) && $this->permission[$type] === false)
        $return = false;

        if($return === true && $table !== null)
        {
            $table = $this->table($table);
            $role = $this->role();
            $return = $table->permissionCan($type,$role);
        }

        return $return;
    }


    // checkPermission
    // envoie une exception si la permission est fausse
    public function checkPermission(string $type,$table=null):self
    {
        if($this->hasPermission($type,$table) !== true)
        static::throw();

        return $this;
    }


    // permission
    // retourne le tableau des permissions racines de la base de données
    public function permission():array
    {
        return $this->permission;
    }


    // setPermission
    // change la valeur de option permission, si value est null, toggle
    public function setPermission(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('permission') === true)? false:true;

        return $this->setOption('permission',$value);
    }


    // setLog
    // change la valeur de option log, si value est null, toggle
    public function setLog(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('log') === true)? false:true;

        return $this->setOption('log',$value);
    }


    // statementException
    // lance une exception de db attrapable en cas d'erreur sur le statement
    public function statementException(?array $option=null,\Exception $exception,...$values):void
    {
        $class = $this->getExceptionClass();
        $message = $exception->getMessage();
        $exception = new $class($message,null,$option);

        if(!empty($values[0]) && is_array($values[0]) && !empty($values[0]['sql']))
        $exception->setQuery(Syntax::emulate($values[0]['sql'],$values[0]['prepare'] ?? null));

        throw $exception;

        return;
    }


    // getExceptionClass
    // retourne la classe d'exception courante à utiliser pour l'objet
    public function getExceptionClass():string
    {
        return $this->exception ?? Exception::class;
    }


    // setExceptionClass
    // change la classe courante pour exception
    public function setExceptionClass($value):void
    {
        if(is_bool($value))
        $value = ($value === true)? CatchableException::class:Exception::class;

        if(is_string($value) && is_subclass_of($value,\Exception::class,true))
        $this->exception = $value;

        else
        static::throw();

        return;
    }


    // makeTables
    // créer les objets dbClasse et tables
    // enregistre la méthode onCloseDown
    // méthode protégé
    protected function makeTables(Main\Extenders $extenders):self
    {
        if(empty($this->tables))
        {
            $this->tables = Tables::newOverload();
            $this->makeClasse($extenders);
            $this->tablesLoad();
            $this->tables()->sortDefault()->readOnly(true);
            Base\Response::onCloseDown([$this,'onCloseDown']);
        }

        else
        static::throw('alreadyExists');

        return $this;
    }


    // tablesLoad
    // charge toutes les tables
    // il n'est pas possible de rafraîchir si l'objet contient déjà des tables, mais n'envoie pas d'erreur
    // une table peut être ignoré via la classe de la table
    // méthode protégé
    protected function tablesLoad():self
    {
        if($this->tables()->isEmpty())
        {
            $showTables = $this->schema()->tables();
            $classe = $this->classe();

            if(!empty($showTables))
            {
                $showTables = Base\Arr::camelCaseParent($showTables);
                $priority = 0;
                $increment = static::getPriorityIncrement();

                foreach ($showTables as $value => $parent)
                {
                    $tableClasse = $classe->tableClasse($value);
                    $class = $tableClasse->table();

                    if(!empty($class))
                    {
                        if(!$class::isIgnored())
                        {
                            $priority += $increment;
                            $attr = ['priority'=>$priority];
                            if(!empty($parent))
                            $attr['parent'] = $parent;

                            $this->tableMake($class,$value,$tableClasse,$attr);
                        }
                    }

                    else
                    static::throw('classEmpty');
                }
            }

            else
            static::throw('noTables');
        }

        return $this;
    }


    // tablesColsLoad
    // charge toutes les tables et les colonnes
    public function tablesColsLoad():self
    {
        if($this->tables()->isEmpty())
        $this->tablesLoad();

        $tables = $this->tables();
        foreach ($tables as $table)
        {
            if($table->isColsEmpty())
            $table->colsLoad();
        }

        return $this;
    }


    // tableMake
    // crée un objet table et ajoute le à tables
    // méthode protégé
    protected function tableMake(string $class,string $value,TableClasse $tableClasse,array $attr):self
    {
        $value = new $class($value,$this,$tableClasse,$attr);

        if($value->attr('ignore') !== true)
        $this->tables()->add($value);

        return $this;
    }


    // tables
    // retourne l'objet tables
    public function tables():Tables
    {
        return $this->tables;
    }


    // makeClasse
    // génère l'objet classe de db
    // méthode protégé
    protected function makeClasse(Main\Extenders $extenders):self
    {
        $closure = $this->getOption('classeClosure');

        if(!empty($closure))
        {
            $closure = $closure->bindTo($this,static::class);
            $this->classe = $closure($extenders);
        }

        else
        $this->classe = Classe::newOverload($extenders,$this->getOption('classe'));

        return $this;
    }


    // classe
    // retourne l'objet classe
    public function classe():Classe
    {
        return $this->classe;
    }


    // makeSchema
    // créer l'objet schema
    // méthode protégé
    protected function makeSchema():self
    {
        $closure = $this->getOption('schemaClosure');

        if(!empty($closure))
        {
            $content = $closure($this);

            if(is_array($content) && !empty($content))
            $this->schema = Schema::newOverload($content,$this);
        }

        if(empty($this->schema))
        $this->schema = Schema::newOverload(null,$this);

        return $this;
    }


    // schema
    // retourne l'objet schema
    public function schema():Schema
    {
        if(empty($this->schema))
        $this->makeSchema();

        return $this->schema;
    }


    // setLang
    // lit ou enlève un objet lang à db
    public function setLang(?Main\Lang $value):self
    {
        $this->lang = $value;

        return $this;
    }


    // hasLang
    // retourne vrai si l'objet db a un objet lang lié
    public function hasLang():bool
    {
        return ($this->lang instanceof Main\Lang)? true:false;
    }


    // lang
    // retourne l'objet lang ou envoie une exception si non existant
    public function lang():Main\Lang
    {
        $return = $this->lang;

        if(!$return instanceof Main\Lang)
        static::throw();

        return $return;
    }


    // label
    // retourne le label d'une base de donnée
    public function label($pattern=null,?string $lang=null,?array $option=null):?string
    {
        return $this->lang()->dbLabel($this->dbName(),$lang,Base\Arr::plus($option,['pattern'=>$pattern]));
    }


    // description
    // retourne la description d'une base de donnée
    public function description($pattern=null,?array $replace=null,?string $lang=null,?array $option=null):?string
    {
        return $this->lang()->dbDescription($this->dbName(),$replace,$lang,Base\Arr::plus($option,['pattern'=>$pattern]));
    }


    // getSqlOption
    // retourne les options pour la classe base sql
    public function getSqlOption(?array $option=null):array
    {
        return Base\Arr::plus(parent::getSqlOption($option),['defaultCallable'=>[$this,'getTableDefault']]);
    }


    // setRole
    // lit un objet rôle à db
    public function setRole(Main\Role $value):self
    {
        $this->role = $value;

        return $this;
    }


    // role
    // retourne l'objet role ou envoie une exception si non existant
    public function role():Main\Role
    {
        $return = $this->role;

        if(!$return instanceof Main\Role)
        static::throw();

        return $return;
    }


    // setCom
    // lit ou enlève un objet com à db
    public function setCom(?Main\Com $value):self
    {
        $this->com = $value;

        return $this;
    }


    // hasCom
    // retourne vrai si l'objet db a un objet com lié
    public function hasCom():bool
    {
        return ($this->com instanceof Main\Com)? true:false;
    }


    // com
    // retourne l'objet com ou envoie une exception si non existant
    public function com():Main\Com
    {
        $return = $this->com;

        if(!$return instanceof Main\Com)
        static::throw();

        return $return;
    }


    // getTableDefault
    // retourne les défauts pour la classe base sql en fonction de la table
    public function getTableDefault(string $table):?array
    {
        return (!empty($this->tables))? $this->table($table)->default():null;
    }


    // hasTable
    // retourne vrai si la ou les tables sont dans l'objet tables
    public function hasTable(...$values):bool
    {
        return (!empty($this->tables))? $this->tables()->exists(...$values):false;
    }


    // table
    // retourne un objet table ou envoie une exception si inexistant
    public function table($table):Table
    {
        $return = $this->tables()->get($table);

        if(!$return instanceof Table)
        static::throw($table,'doesNotExist');

        return $return;
    }


    // query
    // méthode query pour la classe db
    // gère les requêtes avec output row et rows
    // pour les autres requêtes, renvoie à la classe core/pdo
    public function query($value,$output=true)
    {
        $return = null;
        $rows = [];

        if(is_string($output) && static::isRowOutput($output))
        {
            if(!is_array($value))
            static::throw($output,'queryInvalid');

            if(empty($value['type']) || !static::isOutput($value['type'],$output))
            static::throw($output,'invalidForType');

            if(empty($value['table']))
            static::throw($output,'requiresTable');

            $type = static::getRowOutputType($output);
            if(empty($type))
            static::throw('emptyType');
            $value = $this->prepareRow($value,$type);

            if(!array_key_exists('id',$value))
            static::throw($output,'requiresId');

            $table = $this->table($value['table']);

            if($type === 'row')
            $return = $table->$output($value['id']);

            elseif($type === 'rows')
            {
                if(empty($value['id']))
                $return = $table->rowsNew();
                else
                {
                    $ids = array_values((array) $value['id']);
                    $return = $table->$output(...$ids);
                }
            }
        }

        else
        $return = parent::query($value,$output);

        return $return;
    }


    // fromPointer
    // retourne la row ou null à partir d'un pointer
    // possible de fournir un tableau de tables valides en troisième argument
    public function fromPointer(string $value,?array $validTables=null,?string $separator=null):?Row
    {
        $return = null;
        $pointer = Base\Str::pointer($value,$separator);

        if(!empty($pointer))
        {
            if(empty($validTables) || in_array($pointer[0],$validTables,true))
            {
                if($this->hasTable($pointer[0]))
                $return = $this->table($pointer[0])->row($pointer[1]);
            }
        }

        return $return;
    }


    // prepareRow
    // prépare le tableau de requête pour toutes les méthodes row et rows
    // va chercher le ou les ids si nécessaires
    // envoie une exception si le type est incorrect
    public function prepareRow(array $return,string $type):array
    {
        if(in_array($type,['row','rows'],true))
        {
            if(empty($return['id']) || empty($return['whereOnlyId']))
            {
                $output = ($type === 'rows')? 'columns':'column';
                $return['id'] = $this->query($return,$output);
            }
        }

        else
        static::throw();

        return $return;
    }


    // row
    // retourne un objet row ou null après avoir traité un tableau pour une requête sql
    public function row(...$values):?Row
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'row');
    }


    // rowRefresh
    // retourne un objet row ou null après avoir traité un tableau pour une requête sql
    // s'il y a une row, elle ira chercher les dernières valeurs dans la base de donnée
    public function rowRefresh(...$values):?Row
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowRefresh');
    }


    // rowIn
    // retourne un objet row ou null après avoir traité un tableau pour une requête sql
    // retourne seulement la row si elle a déjà été chargé
    public function rowIn(...$values):?Row
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowIn');
    }


    // rowInRefresh
    // retourne un objet row ou null après avoir traité un tableau pour une requête sql
    // retourne seulement la row si elle a déjà été chargé, la ligne se mettra à jour avant d'être retourner
    public function rowInRefresh(...$values):?Row
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowInRefresh');
    }


    // rowOut
    // retourne un objet row ou null après avoir traité un tableau pour une requête sql
    // retourne seulement la row si elle n'est pas chargé
    public function rowOut(...$values):?Row
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowOut');
    }


    // rows
    // retourne un objet rows ou null après avoir traité un tableau pour une requête sql
    public function rows(...$values):?Rows
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rows');
    }


    // rowsRefresh
    // retourne un objet rows ou null après avoir traité un tableau pour une requête sql
    // s'il y a des rows, les lignes se mettront à jour avant d'être retourner
    public function rowsRefresh(...$values):?Rows
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowsRefresh');
    }


    // rowsIn
    // retourne un objet rows ou null après avoir traité un tableau pour une requête sql
    // les rows sont seulement retournés si elles existent déjà
    public function rowsIn(...$values):?Rows
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowsIn');
    }


    // rowsInRefresh
    // retourne un objet rows ou null après avoir traité un tableau pour une requête sql
    // les rows sont seulement retournés si elles existent déjà et se mettront à jour avant d'être retourner
    public function rowsInRefresh(...$values):?Rows
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowsInRefresh');
    }


    // rowsOut
    // retourne un objet rows ou null après avoir traité un tableau pour une requête sql
    // les rows sont seulement retournés si elles n'existent pas déjà
    public function rowsOut(...$values):?Rows
    {
        return $this->query(Syntax::makeSelect(Base\Arr::unshift($values,[$this->primary()]),$this->getSqlOption()),'rowsOut');
    }


    // sql
    // retourne un objet sql lié à la base de données
    public function sql(?string $type=null,$output=true):PdoSql
    {
        return Sql::newOverload($this,$type,$output);
    }


    // reservePrimaryDelete
    // méthode protégé utilisé par reservePrimary pour effacer la ligne venant d'être ajouté
    // permission est mise à off et ensuite true, pas besoin d'avoir la permission delete pour effacer la ligne vide dans cette situation
    protected function reservePrimaryDelete(string $value,int $primary,array $option):?int
    {
        $return = null;
        $this->setPermission(false);
        $return = parent::reservePrimaryDelete($value,$primary,$option);
        $this->setPermission(true);

        return $return;
    }


    // setAutoSave
    // change la valeur de option autoSave, si value est null, toggle
    public function setAutoSave(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('autoSave') === true)? false:true;

        return $this->setOption('autoSave',$value);
    }


    // autoSave
    // sauve toutes les lignes ayant changés dans la base de donnée
    public function autoSave():array
    {
        $return = [];

        $changed = $this->tables()->changed();

        if($changed->isNotEmpty())
        $return = $changed->updateChangedIncluded();

        return $return;
    }


    // tableAttr
    // retourne un tableau des attributs de la table présent dans config de la db
    // peut retourner null, utiliser par table/setAttr
    // à plus de priorité que les attributs de db mais moins que ceux de row
    public function tableAttr($table):?array
    {
        $return = null;

        if($table instanceof Table)
        $table = $table->name();

        if(is_string($table))
        $return = $this->option['tables'][$table] ?? null;

        return $return;
    }


    // colAttr
    // retourne un tableau des attributs de la colonne présent dans config de la db
    // peut retourner null, utiliser par dbClasse, a moins de priorité que table/colAttr
    public function colAttr(string $col):?array
    {
        $return = $this->option['cols'][$col] ?? null;

        if(is_string($return))
        static::throw($col,'stringNotAllowed',$return);

        return $return;
    }


    // info
    // retourne un tableau d'information sur la connexion db
    // inclut le overview de tables si tables est true
    public function info():array
    {
        $return = parent::info();
        $return['tablesInfo'] = $this->tables()->info();

        return $return;
    }


    // isRowOutput
    // retourne vrai si le type de output est row/rows
    public static function isRowOutput($value):bool
    {
        return (is_string($value) && in_array($value,static::$config['output']['row'],true))? true:false;
    }


    // getRowOutputType
    // retourne le type pour row output (row ou rows)
    public static function getRowOutputType(string $value):?string
    {
        $return = null;

        if(static::isRowOutput($value))
        $return = (strpos($value,'rows') === 0)? 'rows':'row';

        return $return;
    }


    // getPriorityIncrement
    // retourne l'incrémentation de priorité souhaité
    public static function getPriorityIncrement():int
    {
        return static::$config['priorityIncrement'];
    }
}

// init
Db::__init();
?>