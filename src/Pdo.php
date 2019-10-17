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

// pdo
// class used to query the database using the PDO object
class Pdo extends Main\Root
{
    // trait
    use Main\_option;
    use Main\_inst;


    // config
    public static $config = [
        'option'=>[ // tableau d'options
            'history'=>true, // les requêtes sont ajoutés à l'historique
            'rollback'=>true, // les rollback de requête sont générés, seulement si le tableau contient la table ainsi qu'un id numérique
            'debug'=>null, // les requêtes émulés sont retournés sans être lancés à la base de donnée
            'cast'=>null, // les valeurs numériques des fetchs sont cast
            'primary'=>'id', // nom de la clé primaire
            'charset'=>'utf8mb4', // charset
            'sql'=>null, // option pour BaseSql
            'connect'=>[ // attribut de connexion
                \PDO::ATTR_DEFAULT_FETCH_MODE=>\PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES=>false,
                \PDO::ATTR_STRINGIFY_FETCHES=>false,
                \PDO::ATTR_ERRMODE=>\PDO::ERRMODE_EXCEPTION]],
        'port'=>3306, // port par défaut
        'fetch'=>[ // tableau associatif pour les fetch mode
            'assoc'=>\PDO::FETCH_ASSOC,
            'assocUnique'=>\PDO::FETCH_ASSOC | \PDO::FETCH_UNIQUE,
            'named'=>\PDO::FETCH_NAMED, // les clés duplicats sont merge et pas replace
            'num'=>\PDO::FETCH_NUM,
            'both'=>\PDO::FETCH_BOTH,
            'obj'=>\PDO::FETCH_OBJ,
            'lazy'=>\PDO::FETCH_LAZY,
            'keyPair'=>\PDO::FETCH_KEY_PAIR,
            'column'=>\PDO::FETCH_COLUMN,
            'columnGroup'=>\PDO::FETCH_GROUP | \PDO::FETCH_COLUMN],
        'output'=>[
            'default'=>[ // output par défaut, pour select et show
                'select'=>'assocs',
                'show'=>'assocs'],
            'all'=>[ // configuration des différents output, arg est un tableau d'argument, limit permet de spécifier une limite sql si type select et non présente
                'assocs'=>['method'=>'fetchAll','fetch'=>'assoc'],
                'assocsUnique'=>['method'=>'fetchAll','fetch'=>'assocUnique'],
                'assocsKey'=>['method'=>'fetchAll','fetch'=>'assoc','key'=>0],
                'assoc'=>['method'=>'fetch','fetch'=>'assoc','selectLimit'=>1],
                'nameds'=>['method'=>'fetchAll','fetch'=>'named'],
                'named'=>['method'=>'fetch','fetch'=>'named','selectLimit'=>1],
                'nums'=>['method'=>'fetchAll','fetch'=>'num'],
                'numsKey'=>['method'=>'fetchAll','fetch'=>'num','key'=>0],
                'num'=>['method'=>'fetch','fetch'=>'num','selectLimit'=>1],
                'boths'=>['method'=>'fetchAll','fetch'=>'both'],
                'both'=>['method'=>'fetch','fetch'=>'both','selectLimit'=>1],
                'objs'=>['method'=>'fetchAll','fetch'=>'obj'],
                'objsKey'=>['method'=>'fetchAll','fetch'=>'obj','key'=>0],
                'obj'=>['method'=>'fetchObject','selectLimit'=>1],
                'lazy'=>['method'=>'fetch','fetch'=>'lazy','selectLimit'=>1],
                'keyPairs'=>['method'=>'fetchAll','fetch'=>'keyPair','arg'=>null],
                'keyPair'=>['method'=>'fetch','fetch'=>'keyPair','selectLimit'=>1,'arg'=>null],
                'segments'=>['method'=>'fetchAll','fetch'=>'segment','arg'=>null],
                'segment'=>['method'=>'fetch','fetch'=>'segment','selectLimit'=>1,'arg'=>null],
                'columns'=>['method'=>'fetchAll','fetch'=>'column','arg'=>0],
                'columnsGroup'=>['method'=>'fetchAll','fetch'=>'columnGroup','arg'=>0],
                'column'=>['method'=>'fetchColumn','arg'=>0,'selectLimit'=>1],
                'rowCount'=>['method'=>'rowCount'],
                'columnCount'=>['method'=>'columnCount','selectLimit'=>1],
                'columnMeta'=>['method'=>'getColumnMeta'],
                'insertId'=>['method'=>'lastInsertId'],
                'info'=>['method'=>'infoStatement'],
                '*'=>['method'=>'infoStatement'],
                'statement'=>[]]],
        'importantVariables'=>array(
            'basedir','datadir','tmpdir','log_error','pid_file','socket','sql_mode','character_sets_dir',
            'character_set_connection','character_set_database','character_set_filesystem',
            'character_set_results','character_set_server','character_set_system',
            'collation_connection','collation_database','collation_server',
            'default_storage_engine','default_tmp_storage_engine')
    ];


    // dynamic
    protected $pdo = null; // valeur qui contient pdo
    protected $dsn = null; // valeur qui content le dsn
    protected $username = null; // valeur qui content le username
    protected $history = null; // objet history


    // construct
    // construction de la classe
    public function __construct(string $dsn,string $username,string $password,?array $option=null)
    {
        $this->option($option);
        $this->setDsn($dsn);
        $this->connect($username,$password);

        return;
    }


    // destruct
    // lors de destruction de la classe
    public function __destruct()
    {
        $this->pdo = null;

        return;
    }


    // invoke
    // appel de la classe, renvoie vers query
    public function __invoke(...$args)
    {
        return $this->query(...$args);
    }


    // toString
    // retourne le nom de la base de donnée
    public function __toString():string
    {
        return $this->name();
    }


    // cast
    // retourne la valeur cast
    public function _cast():string
    {
        return $this->name();
    }


    // onSetInst
    // méthode appeler après setInst
    // méthode protégé
    protected function onSetInst():self
    {
        return $this->checkReady(true);
    }


    // onUnsetInst
    // méthode appeler après unsetInst
    // méthode protégé
    protected function onUnsetInst():self
    {
        return $this;
    }


    // onBeforeMakeStatement
    // callback avant la création du statement dans makeStatement
    // méthode protégé
    protected function onBeforeMakeStatement(array $value):self
    {
        return $this;
    }


    // onAfterMakeStatement
    // callback après la création du statement dans makeStatement
    // méthode protégé
    protected function onAfterMakeStatement(array $value,\PdoStatement $statement):self
    {
        if(!empty($value['type']))
        {
            if($this->getOption('history') === true)
            {
                if(static::isOutput($value['type'],'insertId'))
                $value['insertId'] = $this->lastInsertId();
                $this->history()->add($value,$statement);
            }
        }

        return $this;
    }


    // instName
    // retourne le nom à utiliser pour storage dans inst
    public function instName():string
    {
        return $this->dsn();
    }


    // connect
    // connect à une base de donnée
    public function connect(string $username,string $password):self
    {
        $this->checkReady(false);
        $dsn = static::parseDsn($this->dsn(),$this->charset());

        if(!empty($dsn))
        {
            if(static::isDriver($dsn['scheme']))
            {
                $this->setUsername($username);
                $this->pdo = new \PDO($dsn['dsn'],$username,$password,$this->getOption('connect'));
                $this->makeHistory();
            }

            else
            static::throw('unsupportedDriver');
        }

        else
        static::throw('invalidDsn');

        return $this;
    }


    // disconnect
    // deconnect d'une base de donnée
    public function disconnect():self
    {
        $this->checkReady();
        $this->pdo = null;
        $this->history = null;

        return $this;
    }


    // pdo
    // retourne l'objet pdo
    public function pdo():\Pdo
    {
        return $this->pdo;
    }


    // primary
    // retourne le nom de la clé primaire
    public function primary():string
    {
        return $this->getOption('primary');
    }


    // charset
    // retourne le nom du charset
    public function charset():string
    {
        return $this->getOption('charset');
    }


    // collation
    // retourne la collation de la base de données
    public function collation():?string
    {
        return $this->showVariable('collation_database');
    }


    // dsn
    // retourne le dsn
    public function dsn():string
    {
        return $this->dsn;
    }


    // setDsn
    // change le dsn
    public function setDsn(string $value):self
    {
        $this->checkReady(false);
        $this->dsn = $value;

        return $this;
    }


    // driver
    // retourne le driver du dsn
    public function driver():?string
    {
        return static::parseDsn($this->dsn(),$this->charset())['driver'] ?? null;
    }


    // host
    // retourne le host du dsn
    public function host():?string
    {
        return static::parseDsn($this->dsn(),$this->charset())['host'] ?? null;
    }


    // dbName
    // retourne le dbname du dsn
    public function dbName():?string
    {
        return static::parseDsn($this->dsn(),$this->charset())['dbname'] ?? null;
    }


    // username
    // retourne le username
    public function username():?string
    {
        return $this->username;
    }


    // setUsername
    // change le username
    public function setUsername(string $value):self
    {
        $this->checkReady(false);
        $this->username = $value;

        return $this;
    }


    // name
    // retourne le nom de l'objet db
    public function name():string
    {
        return $this->checkReady()->dsn().'@'.$this->username();
    }


    // clientVersion
    // retourne l'attribut client version
    public function clientVersion():string
    {
        return $this->getAttr(\PDO::ATTR_CLIENT_VERSION);
    }


    // connectionStatus
    // retourne l'attribut connection status
    public function connectionStatus():string
    {
        return $this->getAttr(\PDO::ATTR_CONNECTION_STATUS);
    }


    // serverVersion
    // retourne l'attribut server version
    public function serverVersion():string
    {
        return $this->getAttr(\PDO::ATTR_SERVER_VERSION);
    }


    // serverInfo
    // retourne l'attribut server info
    public function serverInfo():string
    {
        return $this->getAttr(\PDO::ATTR_SERVER_INFO);
    }


    // getSqlOption
    // retourne les options pour la classe base sql
    public function getSqlOption(?array $option=null):array
    {
        return Base\Arr::plus($this->getOption('sql'),['primary'=>$this->primary(),'charset'=>$this->charset(),'quoteCallable'=>[$this,'quote']],$option);
    }


    // setDebug
    // change la valeur de option debug, si value est null, toggle
    public function setDebug(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('debug') === true)? false:true;

        return $this->setOption('debug',$value);
    }


    // isReady
    // retourne vrai si une connection est établi
    public function isReady():bool
    {
        return ($this->pdo instanceof \PDO)? true:false;
    }


    // checkReady
    // lance une exception si le status n'est pas le même que celui donné en argument
    public function checkReady(bool $value=true):self
    {
        $ready = $this->isReady();

        if($value === true && $ready === false)
        static::throw('pdoNotConnected');

        elseif($value === false && $ready === true)
        static::throw('pdoConnected');

        return $this;
    }


    // setRollback
    // change la valeur de option rollback, si value est null, toggle
    public function setRollback(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('rollback') === true)? false:true;

        return $this->setOption('rollback',$value);
    }


    // makeHistory
    // créer l'objet history
    // méthode protégé
    protected function makeHistory():self
    {
        $this->history = History::newOverload();

        return $this;
    }


    // history
    // retourne l'objet de l'historique de db
    public function history():History
    {
        return $this->history;
    }


    // setHistory
    // change la valeur de option history, si value est null, toggle
    public function setHistory(?bool $value=null):self
    {
        if($value === null)
        $value = ($this->getOption('history') === true)? false:true;

        return $this->setOption('history',$value);
    }


    // historyRollback
    // lance le rollback sur une requête dnas l'historique
    // le type est requis et un index peut être spécifié
    public function historyRollback(string $type,int $index=-1,$output=true)
    {
        $return = null;
        $history = $this->history()->typeIndex($type,$index);

        if(!empty($history) && !empty($history['rollback']))
        $return = $this->query($history['rollback'],$output);

        return $return;
    }


    // info
    // retourne un tableau d'information sur la connexion pdo
    public function info():array
    {
        $return = [];
        $this->checkReady(true);

        $return['dsn'] = $this->dsn();
        $return['driver'] = $this->driver();
        $return['username'] = $this->username();
        $return['host'] = $this->host();
        $return['dbname'] = $this->dbName();

        $return['clientVersion'] = $this->clientVersion();
        $return['connectionStatus'] = $this->connectionStatus();
        $return['serverInfo'] = $this->serverInfo();
        $return['serverVersion'] = $this->serverVersion();

        $return['persistent'] = $this->getAttr(\PDO::ATTR_PERSISTENT);
        $return['autocommit'] = $this->getAttr(\PDO::ATTR_AUTOCOMMIT);
        $return['oracleNull'] = $this->getAttr(\PDO::ATTR_ORACLE_NULLS);
        $return['defaultFetchMode'] = $this->getAttr(\PDO::ATTR_DEFAULT_FETCH_MODE);
        $return['emulatePrepare'] = $this->getAttr(\PDO::ATTR_EMULATE_PREPARES);
        $return['importantVariables'] = $this->importantVariables();
        
        $return['historyUni'] = $this->history()->keyValue();
        $return['historyCounts'] = $this->history()->total();

        return $return;
    }

    
    // importantVariables
    // retourne un tableau avec toutes les noms et valeurs des variables importantes, tel que défini dans config
    // output est keyValues
    public function importantVariables(?array $option=null):?array
    {
        return $this->showVariables(static::$config['importantVariables'],$option);
    }
    
    
    // getAttr
    // retourne un attribut de l'objet pdo ou pdoStatement
    public function getAttr(int $key,?\PDOStatement $statement=null)
    {
        $return = null;

        if(!empty($statement))
        $return = $statement->getAttribute($key);

        else
        $return = $this->checkReady()->pdo()->getAttribute($key);

        return $return;
    }


    // setAttr
    // change un attribut de l'objet pdo ou pdoStatement
    public function setAttr(int $key,$value,?\PDOStatement $statement=null):bool
    {
        $return = false;

        if(!empty($statement))
        $return = $statement->setAttribute($key,$value);

        else
        $return = $this->checkReady()->pdo()->setAttribute($key,$value);

        return $return;
    }


    // errorCode
    // retourne un code décrivant la dernière erreur de pdo ou d'un statement
    public function errorCode(?\PDOStatement $statement=null)
    {
        $return = null;

        if(!empty($statement))
        $return = $statement->errorCode();

        else
        $return = $this->checkReady()->pdo()->errorCode();

        return $return;
    }


    // errorInfo
    // retourne un tableau décrivant la dernière erreur de pdo ou d'un statement
    public function errorInfo(?\PDOStatement $statement=null):?array
    {
        $return = null;

        if(!empty($statement))
        $return = $statement->errorInfo();

        else
        $return = $this->checkReady()->pdo()->errorInfo();

        return $return;
    }


    // beginTransaction
    // débute une transaction
    public function beginTransaction():bool
    {
        return $this->checkReady()->pdo()->beginTransaction();
    }


    // inTransaction
    // retourne vrai si une transaction est active
    public function inTransaction():bool
    {
        return $this->checkReady()->pdo()->inTransaction();
    }


    // commit
    // commet la transaction
    public function commit():bool
    {
        return $this->checkReady()->pdo()->commit();
    }


    // rollback
    // annule la transaction
    public function rollback():bool
    {
        return $this->checkReady()->pdo()->rollback();
    }


    // lastInsertId
    // retourne le dernier id inséré
    public function lastInsertId(?string $name=null):?int
    {
        $return = null;
        $this->checkReady();
        $insertId = (int) $this->pdo()->lastInsertId($name);

        if($insertId > 0)
        $return = $insertId;

        return $return;
    }


    // quote
    // quote une variable via pdo
    public function quote($value,?int $type=null):?string
    {
        $return = null;
        $this->checkReady();

        if(is_scalar($value) || $value === null)
        {
            $type = static::parseDataType($value);

            if(is_int($type))
            $return = $this->pdo()->quote($value,$type);
        }

        return $return;
    }


    // makeStatement
    // prend un tableau query et retourne un objet pdo statement
    // gère le try catch
    public function makeStatement($value,?array $attr=[]):?\PDOStatement
    {
        $return = null;
        $value = Syntax::parseReturn($value);

        try
        {
            if($this->checkReady() && !empty($value))
            {
                $this->onBeforeMakeStatement($value);

                if(!empty($value['prepare']) && is_array($value['prepare']))
                $return = $this->preparedStatement($value['sql'],$value['prepare'],$attr);

                else
                {
                    $query = $this->pdo->query($value['sql']);
                    if($query instanceof \PDOStatement)
                    $return = $query;
                }

                if(!empty($return))
                $this->onAfterMakeStatement($value,$return);
            }
        }

        catch (\PDOException $e)
        {
            $this->statementException(null,$e,$value);
        }

        return $return;
    }


    // statementException
    // lance une exception de db attrapable
    public function statementException(?array $option=null,\Exception $exception,...$values):void
    {
        static::throw($exception->getMessage(),null,$option);

        return;
    }


    // infoStatement
    // retourne le maximum d'informations sur le statement selon le type de requête
    public function infoStatement($value,\PDOStatement $statement):?array
    {
        $return = $this->debug($value);

        if(!empty($return))
        {
            $type = $return['type'];
            $return['statement'] = $statement;

            if(static::isOutput($type,'rowCount'))
            $return['row'] = $statement->rowCount();

            if(static::isOutput($type,'insertId'))
            $return['insertId'] = $this->lastInsertId();

            if(static::isOutput($type,'columnCount'))
            {
                $return['all'] = $statement->fetchAll(\PDO::FETCH_ASSOC);
                $return['column'] = $statement->columnCount();
                $return['cell'] = $return['row'] * $return['column'];
                $return['columnMeta'] = $this->getColumnMeta($statement);
            }

            $return['debugDumpParams'] = Base\Buffer::startCallGet([$statement,'debugDumpParams']);
        }

        return $return;
    }


    // outputStatement
    // gère le output pour pdoStatement
    public function outputStatement($value,$output,\PDOStatement $statement)
    {
        $return = null;
        $value = Syntax::parseReturn($value);

        if(!empty($value))
        {
            if(!static::isOutput($value['type'],$output))
            static::throw($output,'invalidOutputFor',$value['type']);

            else
            {
                $output = static::output($value['type'],$output);

                if(!empty($output))
                {
                    if($output['type'] === 'statement')
                    $return = $statement;

                    elseif(!empty($output['method']))
                    {
                        $method = $output['method'];
                        $type = $value['type'];

                        if($method === 'infoStatement')
                        $return = $this->infoStatement($value,$statement);

                        elseif($method === 'rowCount' && static::isOutput($type,$output['type']))
                        $return = $statement->rowCount();

                        elseif($method === 'lastInsertId' && static::isOutput($type,$output['type']))
                        $return = $this->lastInsertId();

                        elseif(in_array($type,['select','show'],true))
                        $return = $this->outputStatementSelectShow($value,$output,$statement);
                    }
                }
            }
        }

        return $return;
    }


    // getColumnMeta
    // retourne un tableau multidimensionnel avec les meta des colonnes du statement
    public function getColumnMeta(\PDOStatement $value):array
    {
        $return = [];

        for ($i=0; $i < $value->columnCount(); $i++)
        {
            $meta = $value->getColumnMeta($i);
            if(!empty($meta['name']))
            {
                $key = $meta['name'];
                $return[$key] = $meta;
            }
        }

        return $return;
    }


    // fetchKeyPairStatement
    // retourne une key pair à partir d'un statement
    // arg peut être des clés, indexes ou null
    // fonctionne même si le statement contient plus de deux colonnes
    public function fetchKeyPairStatement(?array $arg,\PDOStatement $statement):?array
    {
        $return = null;
        $count = $statement->columnCount();
        $arg = Syntax::shortcut(array_values((array) $arg));

        if($count > 2)
        {
            if(!empty($fetch = $statement->fetch(\PDO::FETCH_ASSOC)))
            {
                if(!empty($arg) && count($arg) === 2 && !Base\Arr::onlyNumeric($arg))
                $return = Base\Arr::keyValue($arg[0],$arg[1],$fetch);

                else
                {
                    $arg = (empty($arg) || count($arg) !== 2)? [0,1]:$arg;
                    $return = Base\Arr::keyValueIndex($arg[0],$arg[1],$fetch);
                }
            }
        }

        elseif($count === 2)
        $return = $statement->fetch(\PDO::FETCH_KEY_PAIR);

        return $return;
    }


    // fetchKeyPairsStatement
    // retourne les key pairs à partir d'un statement
    // arg peut être des clés, indexes ou null
    // fonctionne même si le statement contient plus de deux colonnes
    public function fetchKeyPairsStatement(?array $arg,\PDOStatement $statement):?array
    {
        $return = null;
        $count = $statement->columnCount();
        $arg = Syntax::shortcut(array_values((array) $arg));

        if($count > 2)
        {
            if(!empty($fetch = $statement->fetchAll(\PDO::FETCH_ASSOC)))
            {
                if(!empty($arg) && count($arg) === 2 && !Base\Arr::onlyNumeric($arg))
                $return = Base\Column::keyValue($arg[0],$arg[1],$fetch);

                else
                {
                    $arg = (empty($arg) || count($arg) !== 2)? [0,1]:$arg;
                    $return = Base\Column::keyValueIndex($arg[0],$arg[1],$fetch);
                }
            }
        }

        elseif($count === 2)
        $return = $statement->fetchAll(\PDO::FETCH_KEY_PAIR);

        return $return;
    }


    // fetchColumnStatement
    // retourne une colonne d'une ligne à partir d'un statement
    // arg peut être index ou nom de colonne
    public function fetchColumnStatement($arg,\PDOStatement $statement)
    {
        $return = null;
        $arg = Syntax::shortcut(array_values((array) $arg));

        if(!empty($arg) && !Base\Arr::onlyNumeric($arg) && !empty($fetch = $statement->fetch(\PDO::FETCH_ASSOC)))
        $return = Base\Arr::get($arg[0],$fetch);

        else
        {
            $arg = (empty($arg))? [0]:$arg;
            $return = $statement->fetchColumn(...$arg);
        }

        return $return;
    }


    // fetchColumnsStatement
    // retourne une colonne sur toutes les lignes d'un statement
    // arg peut être index ou nom de colonne
    public function fetchColumnsStatement($arg,\PDOStatement $statement):?array
    {
        $return = null;
        $arg = Syntax::shortcut(array_values((array) $arg));

        if(!empty($arg) && !Base\Arr::onlyNumeric($arg) && !empty($fetch = $statement->fetchAll(\PDO::FETCH_ASSOC)))
        $return = Base\Column::value($arg[0],$fetch);

        else
        {
            $arg = (empty($arg))? [0]:$arg;
            $return = $statement->fetchAll(\PDO::FETCH_COLUMN,...$arg);
        }

        return $return;
    }


    // fetchSegmentStatement
    // retourne la string avec segments remplacés
    // arg doit être un tableau contenant la string comme première valeur
    public function fetchSegmentStatement(array $arg,\PDOStatement $statement):?string
    {
        $return = null;
        $arg = current($arg);

        if(is_string($arg) && !empty($arg) && !empty($fetch = $statement->fetch(\PDO::FETCH_ASSOC)))
        $return = Base\Segment::sets(null,$fetch,$arg);

        return $return;
    }


    // fetchSegmentsStatement
    // retounre un tableau avec les ids comme clés et la string avec segments remplacés comme valeur
    // arg doit être un tableau contenant la string comme première valeur
    // une exception peut être envoyé si la clé est invalide ou déjà existante dans le tableau de retour
    public function fetchSegmentsStatement(array $arg,\PDOStatement $statement):?array
    {
        $return = null;
        $arg = current($arg);

        if(is_string($arg) && !empty($arg) && is_array($fetch = $statement->fetchAll(\PDO::FETCH_ASSOC)))
        {
            $return = [];

            foreach ($fetch as $value)
            {
                if(is_array($value))
                {
                    $k = current($value);

                    if(Base\Arr::isKey($k) && !array_key_exists($k,$return))
                    $return[$k] = Base\Segment::sets(null,$value,$arg);

                    else
                    static::throw('invalidKey',$k);
                }
            }
        }

        return $return;
    }


    // query
    // méthode pour effectuer des requetes à la base de données
    // la requête n'est pas lancé si option debug est true ou output est debug
    // si output est un tableau avec clé beforeAfter, possibilité de retourner la ligne avant et/ou après le insert, update ou delete
    public function query($value,$output=true)
    {
        $return = null;

        if($this->getOption('debug') || $output === 'debug')
        $return = $this->debug($value);

        elseif(!empty($value = Syntax::parseReturn($value)))
        {
            $beforeAfter = (is_array($output) && array_key_exists('beforeAfter',$output) && in_array($value['type'],['insert','update','delete'],true))? true:false;

            if($beforeAfter === true)
            {
                $return = [];
                $return['before'] = $this->queryBeforeAfter('before',$value,$output['beforeAfter']);
            }

            $statement = $this->makeStatement($value);

            if(!empty($statement))
            {
                if($beforeAfter === true)
                {
                    $return['query'] = $this->outputStatement($value,true,$statement);

                    if($return['query'] !== $statement)
                    $statement->closeCursor();

                    $return['after'] = $this->queryBeforeAfter('after',$value,$output['beforeAfter']);
                }

                else
                {
                    $return = $this->outputStatement($value,$output,$statement);

                    if($return !== $statement)
                    $statement->closeCursor();
                }
            }
        }

        return $return;
    }


    // statement
    // effectue la requête à la base de donnée et retourne l'objet pdoStatement
    public function statement($value):?\PDOStatement
    {
        return $this->query($value,null);
    }


    // queryBeforeAfter
    // permet de retourner le contenu d'une ou plusieurs lignes avant ou après les changements effectués via insert, update ou delete
    // méthode protégé
    protected function queryBeforeAfter(string $type,array $value,$output=true)
    {
        $return = null;

        if(in_array($type,['before','after'],true) && Syntax::isReturnSelect($value))
        {
            if($type === 'before')
            $return = $this->query($value['select'],$output);

            elseif($type === 'after')
            $return = $this->query($value['select'],$output);
        }

        return $return;
    }


    // preparedStatement
    // construit un prepared statement
    // méthode protégé
    protected function preparedStatement(string $value,array $prepare,?array $attr=[]):?\PDOStatement
    {
        $return = null;
        $query = $this->pdo()->prepare($value,$attr);

        if($query instanceof \PDOStatement)
        {
            foreach ($prepare as $k => $v)
            {
                $dataType = static::parseDataType($v);

                if(is_int($dataType))
                {
                    if(is_int($k))
                    $query->bindValue($k,$v,$dataType);

                    else
                    $query->bindValue(":$k",$v,$dataType);
                }
            }

            $execute = $query->execute();

            if($execute === true)
            $return = $query;
        }

        return $return;
    }


    // outputStatementSelectShow
    // gère le output pour pdoStatement pour select ou show
    // méthode protégé
    protected function outputStatementSelectShow(array $value,array $output,\PDOStatement $statement)
    {
        $return = null;

        if(!empty($output) && !empty($output['method']) && !empty($value))
        {
            $method = $output['method'];
            $arg = (array_key_exists('arg',$output) && is_array($output['arg']))? array_values($output['arg']):[];
            $cast = (!empty($this->getOption('cast')) || !empty($value['cast']))? true:false;

            if($method === 'columnCount')
            $return = $statement->columnCount();

            elseif($method === 'getColumnMeta')
            $return = $this->getColumnMeta($statement);

            elseif($method === 'fetchAll')
            {
                if($output['fetch'] === \PDO::FETCH_KEY_PAIR)
                $return = $this->fetchKeyPairsStatement($arg,$statement);

                elseif($output['fetch'] === \PDO::FETCH_COLUMN)
                $return = $this->fetchColumnsStatement($arg,$statement);

                elseif($output['fetch'] === 'segment')
                $return = $this->fetchSegmentsStatement($arg,$statement);

                else
                $return = $statement->fetchAll($output['fetch'],...$arg);

                if($cast === true && is_array($return))
                $return = Base\Arr::cast($return);

                if(array_key_exists('key',$output) && is_scalar($output['key']))
                $return = static::outputKey($output['key'],$return);
            }

            elseif($statement->rowCount())
            {
                if($method === 'fetch')
                {
                    if($output['fetch'] === \PDO::FETCH_KEY_PAIR)
                    $return = $this->fetchKeyPairStatement($arg,$statement);

                    elseif($output['fetch'] === 'segment')
                    $return = $this->fetchSegmentStatement($arg,$statement);

                    else
                    $return = $statement->fetch($output['fetch'],...$arg);
                }

                elseif($method === 'fetchColumn')
                $return = $this->fetchColumnStatement($arg,$statement);

                elseif($method === 'fetchObject')
                $return = $statement->fetchObject(...$arg);

                if($cast === true && is_numeric($return))
                $return = Base\Number::cast($return);
            }
        }

        return $return;
    }


    // make
    // construit et soumet une requête généré par la classe sql
    public function make(string $type,array $array,$output=true,?array $option=null)
    {
        $return = null;

        if(Syntax::isQuery($type))
        {
            $method = 'make'.ucfirst(strtolower($type));
            $return = $this->$method($array,$output,$option);
        }

        return $return;
    }


    // makeSelect
    // construit et soumet une requête select généré par la classe sql
    // une valeur numérique limit peut être ajouté dans le tableau input sql si le type est select
    // par exemple pour select assoc, limit 1 est ajouté
    public function makeSelect(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeSelect(static::selectLimit($output,$array),$this->getSqlOption($option)),$output);
    }


    // makeShow
    // construit et soumet une requête show généré par la classe sql
    public function makeShow(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeShow($array,$this->getSqlOption($option)),$output);
    }


    // makeInsert
    // construit et soumet une requête insert généré par la classe sql
    // possible de faire une requête insertion vide si un tableau vide est fourni comme insertSet
    // option rollback possible
    public function makeInsert(array $array,$output=true,?array $option=null)
    {
        $return = null;
        $option = $this->getSqlOption($option);
        $sql = Syntax::makeInsert($array,$option);

        if(!empty($sql))
        {
            if($this->getOption('rollback'))
            $sql = $this->prepareRollback('insert',$sql,$option);

            $return = $this->query($sql,$output);
        }

        return $return;
    }


    // makeUpdate
    // construit et soumet une requête update généré par la classe sql
    // option rollback possible
    public function makeUpdate(array $array,$output=true,?array $option=null)
    {
        $return = null;
        $option = $this->getSqlOption($option);
        $sql = Syntax::makeUpdate($array,$option);

        if(!empty($sql))
        {
            if($this->getOption('rollback'))
            $sql = $this->prepareRollback('update',$sql,$option);

            $return = $this->query($sql,$output);
        }

        return $return;
    }


    // makeDelete
    // construit et soumet une requête delete généré par la classe sql
    // option rollback possible
    public function makeDelete(array $array,$output=true,?array $option=null)
    {
        $return = null;
        $option = $this->getSqlOption($option);
        $sql = Syntax::makeDelete($array,$option);

        if(!empty($sql))
        {
            if($this->getOption('rollback'))
            $sql = $this->prepareRollback('delete',$sql,$option);

            $return = $this->query($sql,$output);
        }

        return $return;
    }


    // makeCreate
    // construit et soumet une requête create généré par la classe sql
    public function makeCreate(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeCreate($array,$this->getSqlOption($option)),$output);
    }


    // makeAlter
    // construit et soumet une requête alter généré par la classe sql
    public function makeAlter(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeAlter($array,$this->getSqlOption($option)),$output);
    }


    // makeTruncate
    // construit et soumet une requête truncate généré par la classe sql
    public function makeTruncate(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeTruncate($array,$this->getSqlOption($option)),$output);
    }


    // makeDrop
    // construit et soumet une requête drop généré par la classe sql
    public function makeDrop(array $array,$output=true,?array $option=null)
    {
        return $this->query(Syntax::makeDrop($array,$this->getSqlOption($option)),$output);
    }


    // prepareRollback
    // prépare la requête rollback pour une requête insert, update ou delete
    // il y aura seulement un rollback si le tableau sql contient select, une table et un id numérique
    public function prepareRollback(string $type,array $return,?array $option=null):array
    {
        if(in_array($type,['insert','update','delete'],true) && Syntax::isReturnRollback($return))
        {
            $table = $return['select']['table'];
            $id = $return['select']['id'];

            if($type === 'insert')
            $return['rollback'] = Syntax::makeDelete([$table,$id],$option);

            else
            {
                $assoc = $this->query($return['select'],'assoc');

                if(!empty($assoc))
                {
                    if($type === 'update')
                    $return['rollback'] = Syntax::makeUpdate([$table,$assoc,$id],$option);

                    elseif($type === 'delete')
                    $return['rollback'] = Syntax::makeInsert([$table,$assoc,$id],$option);

                    $return['rollback']['content'] = $assoc;
                }
            }
        }

        return $return;
    }


    // select
    // construit et soumet une requête select généré par la classe sql
    // les arguments sont pack et output est toujours true
    public function select(...$values)
    {
        return $this->makeSelect($values,true);
    }


    // selectAssoc
    // construit et soumet une requête select avec output assoc
    public function selectAssoc(...$values):?array
    {
        return $this->makeSelect($values,'assoc');
    }


    // selectAssocs
    // construit et soumet une requête select avec output assocs
    public function selectAssocs(...$values):?array
    {
        return $this->makeSelect($values,'assocs');
    }


    // selectAssocsUnique
    // construit et soumet une requête select avec output assocsUnique
    public function selectAssocsUnique(...$values):?array
    {
        return $this->makeSelect($values,'assocsUnique');
    }


    // selectAssocsKey
    // construit et soumet une requête select avec output assocsKey
    // key est le champ qui sera utilisé pour la clé du tableau de retour, peut aussi être un index
    public function selectAssocsKey($key,...$values):?array
    {
        return $this->makeSelect($values,['assocsKey','key'=>Base\Obj::cast($key)]);
    }


    // selectAssocsPrimary
    // construit et soumet une requête select avec output assocsKey
    // les clés du tableau sont primary
    // le colonne clé primaire est ajouté dans what si what n'est pas *
    public function selectAssocsPrimary(...$values):?array
    {
        return $this->makeSelect($values,['assocsKey','key'=>$this->primary()]);
    }


    // selectColumnIndex
    // construit et soumet une requête select avec output column et un index
    public function selectColumnIndex(int $index,...$values)
    {
        return $this->makeSelect($values,['column','arg'=>$index]);
    }


    // selectColumnsIndex
    // construit et soumet une requête select avec output columns et un index
    public function selectColumnsIndex(int $index,...$values):?array
    {
        return $this->makeSelect($values,['columns','arg'=>$index]);
    }


    // selectRowCount
    // construit et soumet une requête select avec output rowCount
    public function selectRowCount(...$values):?int
    {
        return $this->makeSelect($values,'rowCount');
    }


    // selectColumnCount
    // construit et soumet une requête select avec output columnCount
    public function selectColumnCount(...$values):?int
    {
        return $this->makeSelect($values,'columnCount');
    }


    // show
    // construit et soumet une requête show généré par la classe sql
    // les arguments sont pack et output est toujours true
    public function show(...$values)
    {
        return $this->makeShow($values,true);
    }


    // showAssoc
    // construit et soumet une requête show avec output assoc
    public function showAssoc(...$values):?array
    {
        return $this->makeShow($values,'assoc');
    }


    // showAssocs
    // construit et soumet une requête show avec output assocs
    public function showAssocs(...$values):?array
    {
        return $this->makeShow($values,'assocs');
    }


    // showAssocsKey
    // construit et soumet une requête show avec output assocsKey
    // key est le champ qui sera utilisé pour la clé du tableau de retour, peut aussi être un index
    public function showAssocsKey($key,...$values):?array
    {
        return $this->makeShow($values,['assocsKey','key'=>Base\Obj::cast($key,3)]);
    }


    // showColumn
    // construit et soumet une requête show avec output column et un what qui doit être string ou int
    public function showColumn($what,...$values)
    {
        return $this->makeShow($values,['column','arg'=>Base\Obj::cast($what,3)]);
    }


    // showColumns
    // construit et soumet une requête show avec output columns et un what qui doit être string ou int
    public function showColumns($what,...$values):?array
    {
        return $this->makeShow($values,['columns','arg'=>Base\Obj::cast($what,3)]);
    }


    // showKeyValue
    // construit et soumet une requête show avec output keyValue
    public function showKeyValue($key,$pair,...$values):?array
    {
        return $this->makeShow($values,['keyPair','arg'=>[Base\Obj::cast($key,3),Base\Obj::cast($pair,3)]]);
    }


    // showKeyValues
    // construit et soumet une requête show avec output keyValues
    public function showKeyValues($key,$pair,...$values):?array
    {
        return $this->makeShow($values,['keyPairs','arg'=>[Base\Obj::cast($key,3),Base\Obj::cast($pair,3)]]);
    }


    // showCount
    // construit et soumet une requête show avec output rowCount
    public function showCount(...$values):?int
    {
        return $this->makeShow($values,'rowCount');
    }


    // showColumnCount
    // construit et soumet une requête show avec output columnCount
    public function showColumnCount(...$values):?int
    {
        return $this->makeShow($values,'columnCount');
    }


    // insert
    // construit et soumet une requête insert généré par la classe sql
    // possible de faire une requête insertion vide si un tableau vide est fourni comme insertSet
    // les arguments sont pack et output est true, ce qui est insertId
    public function insert(...$values)
    {
        return $this->makeInsert($values,true);
    }


    // inserts
    // le premier argument est le nom des champs
    // construit et soumet plusieurs requête insert généré par la classe sql
    // important: ceci ne génère pas une requête avec plusieurs insertions
    // possible de faire une requête insertion vide si un tableau vide est fourni comme insertSet
    // du au format de inserts, les valeurs à insérer ne peuvent pas gérés les callables
    // retourne un tableau avec tous les insert ids
    // une exception peut être envoyé si une insertion ne couvre pas tous les fields
    public function inserts($table,array $fields,array ...$values):array
    {
        $return = [];
        $count = count($fields);
        $fields = Base\Obj::cast($fields);

        foreach ($values as $value)
        {
            if(count($value) === $count)
            {
                $set = Base\Arr::combine($fields,$value);
                $insert = $this->insert($table,$set);
                $return[] = $insert;
            }

            else
            static::throw('allFieldsMustBeIncluded');
        }

        return $return;
    }


    // insertBeforeAfter
    // construit et soumet une requête insert avec output beforeAfter assoc
    public function insertBeforeAfter(...$values):?array
    {
        return $this->makeInsert($values,['beforeAfter'=>'assoc']);
    }


    // insertBeforeAfters
    // construit et soumet une requête insert avec output beforeAfter assocs
    public function insertBeforeAfters(...$values):?array
    {
        return $this->makeInsert($values,['beforeAfter'=>'assocs']);
    }


    // update
    // construit et soumet une requête update généré par la classe sql
    // les arguments sont pack et output est true, ce qui est rowCount
    public function update(...$values)
    {
        return $this->makeUpdate($values,true);
    }


    // updateBeforeAfter
    // construit et soumet une requête update avec output beforeAfter assoc
    public function updateBeforeAfter(...$values):?array
    {
        return $this->makeUpdate($values,['beforeAfter'=>'assoc']);
    }


    // updateBeforeAfters
    // construit et soumet une requête update avec output beforeAfter assocs
    public function updateBeforeAfters(...$values):?array
    {
        return $this->makeUpdate($values,['beforeAfter'=>'assocs']);
    }


    // delete
    // construit et soumet une requête delete généré par la classe sql
    // les arguments sont pack et output est toujours true, ce qui est rowCount
    public function delete(...$values)
    {
        return $this->makeDelete($values,true);
    }


    // deleteBeforeAfter
    // construit et soumet une requête delete avec output beforeAfter assoc
    public function deleteBeforeAfter(...$values):?array
    {
        return $this->makeDelete($values,['beforeAfter'=>'assoc']);
    }


    // deleteBeforeAfters
    // construit et soumet une requête delete avec output beforeAfter assocs
    public function deleteBeforeAfters(...$values):?array
    {
        return $this->makeDelete($values,['beforeAfter'=>'assocs']);
    }


    // create
    // construit et soumet une requête create généré par la classe sql
    // les arguments sont pack et output est true, ce qui est statement
    public function create(...$values)
    {
        return $this->makeCreate($values,true);
    }


    // alter
    // construit et soumet une requête alter généré par la classe sql
    // les arguments sont pack et output est true, ce qui est statement
    public function alter(...$values)
    {
        return $this->makeAlter($values,true);
    }


    // truncate
    // construit et soumet une requête truncate généré par la classe sql
    // les arguments sont pack et output est true, ce qui est statement
    public function truncate(...$values)
    {
        return $this->makeTruncate($values,true);
    }


    // drop
    // construit et soumet une requête drop généré par la classe sql
    // les arguments sont pack et output est true, ce qui est statement
    public function drop(...$values)
    {
        return $this->makeDrop($values,true);
    }


    // reservePrimary
    // réserve une clé primaire dans une table et retourne le id
    // la ligne est crée et immédiatemment effacé
    public function reservePrimary($value,?array $option=null):?int
    {
        $return = null;
        $option = Base\Arr::plus(['primary'=>$this->primary()],$option);
        $value = Base\Obj::cast($value,1);
        $primary = $this->makeInsert([$value,[]],true,$option);

        if(is_int($primary))
        $return = $this->reservePrimaryDelete($value,$primary,$option);

        return $return;
    }


    // reservePrimaryDelete
    // méthode protégé utilisé par reservePrimary pour effacer la ligne venant d'être ajouté
    protected function reservePrimaryDelete(string $value,int $primary,array $option):?int
    {
        $return = null;
        $delete = $this->makeDelete([$value,$primary],true,$option);

        if($delete === 1)
        $return = $primary;

        return $return;
    }


    // selectCount
    // construit et soumet une requête select count généré par la classe sql, pas besoin de donner what
    // plus rapide que selectRowCount
    // output est column
    public function selectCount(...$values):?int
    {
        return $this->query(Syntax::makeSelectCount(static::selectLimit('column',$values),$this->getSqlOption()),'column');
    }


    // selectAll
    // construit et soumet une requête select avec output assoc
    // what est *
    public function selectAll(...$values)
    {
        return $this->query(Syntax::makeSelectAll(static::selectLimit('assoc',$values),$this->getSqlOption()),'assoc');
    }


    // selectAlls
    // construit et soumet une requête select avec output assocs
    // what est *
    public function selectAlls(...$values):?array
    {
        return $this->query(Syntax::makeSelectAll($values,$this->getSqlOption()),'assocs');
    }


    // selectAllsKey
    // construit et soumet une requête select avec output assocsKey
    // what est *, key est le champ qui sera utilisé pour la clé du tableau de retour, peut aussi être un index
    public function selectAllsKey($key,...$values):?array
    {
        return $this->query(Syntax::makeSelectAll($values,$this->getSqlOption()),['assocsKey','key'=>Base\Obj::cast($key,3)]);
    }


    // selectAllsPrimary
    // construit et soumet une requête select avec output assocsKey
    // what est *, les clés du tableau sont primary
    public function selectAllsPrimary(...$values):?array
    {
        return $this->query(Syntax::makeSelectAll($values,$this->getSqlOption()),['assocsKey','key'=>$this->primary()]);
    }


    // selectFunction
    // construit et soumet une requête select avec output column
    // what et function doivent être fourni
    public function selectFunction($what,string $function,...$values)
    {
        return $this->query(Syntax::makeSelectFunction($what,$function,static::selectLimit('column',$values,1),$this->getSqlOption()),'column');
    }


    // selectFunctions
    // construit et soumet une requête select avec output columns
    // what et function doivent être fourni
    public function selectFunctions($what,string $function,...$values)
    {
        return $this->query(Syntax::makeSelectFunction($what,$function,$values,$this->getSqlOption()),'columns');
    }


    // selectDistinct
    // construit et soumet une requête select avec output columns
    // what est distinct what
    public function selectDistinct($what,...$values)
    {
        return $this->query(Syntax::makeSelectDistinct($what,$values,$this->getSqlOption()),'columns');
    }


    // selectColumn
    // construit et soumet une requête select avec output column
    // what peut être string ou array pour 1 colonne
    public function selectColumn($what,...$values)
    {
        return $this->query(Syntax::makeSelectColumn($what,static::selectLimit('column',$values,1),$this->getSqlOption()),'column');
    }


    // selectColumns
    // construit et soumet une requête select avec output columns
    // what peut être string ou array pour 1 colonne
    public function selectColumns($what,...$values):?array
    {
        return $this->query(Syntax::makeSelectColumn($what,$values,$this->getSqlOption()),'columns');
    }


    // selectKeyPair
    // construit et soumet une requête select avec output keyValue
    public function selectKeyPair($key,$pair,...$values):?array
    {
        return $this->query(Syntax::makeselectKeyPair($key,$pair,static::selectLimit('keyPair',$values,1),$this->getSqlOption()),'keyPair');
    }


    // selectKeyPairs
    // construit et soumet une requête select avec output keyValues
    public function selectKeyPairs($key,$pair,...$values):?array
    {
        return $this->query(Syntax::makeselectKeyPair($key,$pair,$values,$this->getSqlOption()),'keyPairs');
    }


    // selectPrimary
    // construit et soumet une requête select avec output column
    // what est primary
    public function selectPrimary(...$values)
    {
        return $this->query(Syntax::makeselectPrimary(static::selectLimit('column',$values,1),$this->getSqlOption()),'column');
    }


    // selectPrimaries
    // construit et soumet une requête select avec output columns
    // what est primary
    public function selectPrimaries(...$values):?array
    {
        return $this->query(Syntax::makeselectPrimary($values,$this->getSqlOption()),'columns');
    }


    // selectPrimaryPair
    // construit et soumet une requête select avec output keyValue
    // la key est primary
    public function selectPrimaryPair($pair,...$values):?array
    {
        return $this->query(Syntax::makeselectPrimaryPair($pair,static::selectLimit('keyPair',$values,1),$this->getSqlOption()),'keyPair');
    }


    // selectPrimaryPairs
    // construit et soumet une requête select avec output keyValues
    // la key est primary
    public function selectPrimaryPairs($pair,...$values):?array
    {
        return $this->query(Syntax::makeselectPrimaryPair($pair,$values,$this->getSqlOption()),'keyPairs');
    }


    // selectSegment
    // construit et soumet une requête select avec output segment
    // key doit être une string avec segment []
    public function selectSegment(string $key,...$values):?string
    {
        return $this->query(Syntax::makeSelectSegment($key,static::selectLimit('segment',$values,1),$this->getSqlOption()),['segment','arg'=>$key]);
    }


    // selectSegments
    // construit et soumet une requête select avec output segments
    // la clé du tableau sera la clé primaire
    // key doit être une string avec segment []
    public function selectSegments(string $key,...$values):?array
    {
        return $this->query(Syntax::makeSelectSegment($key,$values,$this->getSqlOption()),['segments','arg'=>$key]);
    }


    // selectSegmentAssoc
    // construit et soumet une requête select avec output assoc
    // key doit être une string avec segment []
    public function selectSegmentAssoc(string $key,...$values):?array
    {
        return $this->query(Syntax::makeSelectSegment($key,static::selectLimit('segment',$values,1),$this->getSqlOption()),'assoc');
    }


    // selectSegmentAssocs
    // construit et soumet une requête select avec output assocs
    // l'argument key doit être une string avec segment []
    public function selectSegmentAssocs(string $key,...$values):?array
    {
        return $this->query(Syntax::makeSelectSegment($key,$values,$this->getSqlOption()),'assocs');
    }


    // selectSegmentAssocsKey
    // construit et soumet une requête select avec output assocsKey
    // la clé primaire sera la clé du tableau
    // l'argument key doit être une string avec segment []
    public function selectSegmentAssocsKey(string $key,...$values):?array
    {
        return $this->query(Syntax::makeSelectSegment($key,$values,$this->getSqlOption()),['assocsKey','key'=>$this->primary()]);
    }


    // selectTableColumnCount
    // fait une requête pour obtenir le nombre des colonnes dans une table
    // utilise select car plus rapide, output est rowCount
    public function selectTableColumnCount($value,?array $option=null):?int
    {
        return $this->query(Syntax::makeSelect(['*',$value,'limit'=>0],$this->getSqlOption($option)),'columnCount');
    }


    // showDatabase
    // retourne le nom de la première database trouvé
    // value doit être une string qui représente like
    // output est column
    public function showDatabase($value,?array $option=null)
    {
        return $this->query(Syntax::makeShowDatabase(Base\Obj::cast($value,1),$this->getSqlOption($option)),'column');
    }


    // showDatabases
    // retourne un tableau avec tous les noms de database
    // value peut être une string qui représente like
    // output est columns
    public function showDatabases($value=null,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowDatabase($value,$this->getSqlOption($option)),'columns');
    }


    // showVariable
    // retourne la valeur de la première variable trouvé
    // value doit être une string qui représente like
    // output est column
    public function showVariable($value,?array $option=null)
    {
        return $this->query(Syntax::makeShowVariable(Base\Obj::cast($value,1),$this->getSqlOption($option)),['column','arg'=>1]);
    }


    // showVariables
    // retourne un tableau avec toutes les noms et valeurs des variables
    // value peut être une string qui représente like
    // output est keyValues
    public function showVariables($value=null,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowVariable($value,$this->getSqlOption($option)),'keyPairs');
    }

    
    // showTable
    // retourne le nom de la première table trouvé
    // value doit être une string qui représente like
    // output est column
    public function showTable($value,?array $option=null)
    {
        return $this->query(Syntax::makeShowTable(Base\Obj::cast($value,1),$this->getSqlOption($option)),'column');
    }


    // showTables
    // retourne un tableau avec tous les noms de table dans la base de donnée
    // value peut être une string qui représente like
    // output est columns
    public function showTables($value=null,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowTable($value,$this->getSqlOption($option)),'columns');
    }


    // showTableStatus
    // output est assoc
    public function showTableStatus($value,?array $option=null)
    {
        return $this->query(Syntax::makeShowTableStatus(Base\Obj::cast($value,1),$this->getSqlOption($option)),'assoc');
    }


    // showTableAutoIncrement
    // retourne la valeur auto increment courante de la table
    public function showTableAutoIncrement($value,?array $option=null):?int
    {
        $return = null;
        $status = $this->showTableStatus($value,$option);

        if(is_array($status) && array_key_exists('Auto_increment',$status))
        $return = $status['Auto_increment'];

        return $return;
    }


    // showTablesColumns
    // retourne un tableau multidimensionnel détailéé de toutes les tables avec les descriptions de toutes les colonnes
    public function showTablesColumns($value=null,?array $option=null):?array
    {
        $return = null;
        $tables = static::showTables($value,$option);

        if(!empty($tables))
        {
            $return = [];
            foreach ($tables as $table)
            {
                $return[$table] = static::showTableColumns($table,$option);
            }
        }

        return $return;
    }


    // showTableColumn
    // fait une requête de type show pour obtenir la description d'une colonne dans une table
    // output est assoc
    public function showTableColumn($table,$value,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowTableColumn($table,Base\Obj::cast($value,1),$this->getSqlOption($option)),'assoc');
    }


    // showTableColumnField
    // fait une requête de type show pour vérifier l'existence d'une colonne dans une table
    // output est column avec champ field
    public function showTableColumnField($table,$value,?array $option=null)
    {
        $option = Base\Arr::plus($option,['full'=>false]);
        return $this->query(Syntax::makeShowTableColumn($table,Base\Obj::cast($value,1),$this->getSqlOption($option)),['column','arg'=>'Field']);
    }


    // showTableColumns
    // fait une requête de type show pour obtenir le nom des colonnes dans une table
    // output est assocsKey, la clé est field
    public function showTableColumns($value,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowTableColumn($value,null,$this->getSqlOption($option)),['assocsKey','key'=>'Field']);
    }


    // showTableColumnsField
    // fait une requête de type show pour obtenir le nom des colonnes dans une table
    // output est columns, la clé est field
    public function showTableColumnsField($value,?array $option=null):?array
    {
        return $this->query(Syntax::makeShowTableColumn($value,null,$this->getSqlOption($option)),['columns','arg'=>'Field']);
    }


    // updateColumn
    // change la valeur d'une colonne
    // output est true par défaut
    public function updateColumn($what,$value,$table,$where=null,$output=true,?array $option=null)
    {
        return $this->makeUpdate([$table,[Base\Obj::cast($what,1)=>$value],$where],$output,$option);
    }


    // updateIncrement
    // incrément la valeur d'une colonne
    // output est true par défaut
    public function updateIncrement($what,int $amount=1,$table,$where=null,$output=true,?array $option=null)
    {
        $return = null;
        $what = Base\Obj::cast($what,1);
        $table = Base\Obj::cast($table,1);

        if($amount > 0)
        {
            $cell = $this->makeSelect([$what,$table,$where],'column');

            if(is_numeric($cell))
            {
                $cell = (int) $cell + $amount;
                $return = $this->makeUpdate([$table,[$what=>$cell],$where],$output,$option);
            }
        }

        return $return;
    }


    // updateDecrement
    // décrement la valeur d'une colonne
    // output est true par défaut
    public function updateDecrement($what,int $amount=1,$table,$where=null,$output=true,?array $option=null)
    {
        $return = null;
        $what = Base\Obj::cast($what,1);
        $table = Base\Obj::cast($table,1);

        if($amount > 0)
        {
            $cell = $this->makeSelect([$what,$table,$where],'column');

            if(is_numeric($cell))
            {
                $cell = (int) $cell - $amount;
                $return = $this->makeUpdate([$table,[$what=>$cell],$where],$output,$option);
            }
        }

        return $return;
    }


    // getDeleteTrimPrimaries
    // retourne les ids de toutes les lignes qui seraient effacés par delete trim
    public function getDeleteTrimPrimaries($table,int $limit,?array $option=null):?array
    {
        $return = null;
        $primary = $this->primary();

        if(!empty($table) && $limit > 0)
        {
            $order = [$primary=>'DESC'];
            $limit = [true,$limit];
            $return = $this->makeSelect([$primary,$table,null,$order,$limit],'columns');
        }

        return $return;
    }


    // deleteTrim
    // efface toutes les lignes de la table plus ancienne que la limite
    // output est true
    public function deleteTrim($table,int $limit,?array $option=null):?int
    {
        $return = null;
        $primary = $this->primary();

        if(!empty($table) && $limit > 0)
        {
            $order = [$primary=>'DESC'];
            $limit = [$limit,($limit - 1)];

            $cell = $this->makeSelect([$primary,$table,null,$order,$limit],'column');

            if($cell !== null)
            {
                $where = [[$primary,'<',$cell]];
                $return = $this->makeDelete([$table,$where],true,$option);
            }
        }

        return $return;
    }


    // alterAutoIncrement
    // fait une requête de type alter pour changer le autoIncrement d'une table
    // output est statement
    public function alterAutoIncrement($table,int $value=0,?array $option=null):?\PDOStatement
    {
        return $this->query(Syntax::makeAlterAutoIncrement($table,$value,$this->getSqlOption($option)),null);
    }


    // emulate
    // émule une requête sql en utilisant la méthode quote de pdo
    public function emulate(string $return,?array $prepare=null,bool $replaceDoubleEscape=true):string
    {
        return Syntax::emulate($return,$prepare,[$this,'quote'],$replaceDoubleEscape);
    }


    // debug
    // retourne le maximum d'informations à partir du tableau de requête sql
    // la requête est émulé en utilisant la méthode quote de pdo
    public function debug($value,bool $replaceDoubleEscape=true):?array
    {
        return Syntax::debug($value,[$this,'quote'],$replaceDoubleEscape);
    }


    // sql
    // retourne un objet sql lié à la base de données
    public function sql(?string $type=null,$output=true):PdoSql
    {
        return PdoSql::newOverload($this,$type,$output);
    }


    // isDriver
    // retourne vrai si le driver est supporté par PDO
    public static function isDriver($value):bool
    {
        return (is_string($value) && in_array($value,static::allDrivers(),true))? true:false;
    }


    // isOutput
    // retourne vrai si l'output est compatible avec le type de requête
    public static function isOutput(string $type,$value):bool
    {
        $return = false;

        if(in_array($value,[true,null,'debug','info','*','statement'],true))
        $return = true;

        elseif(is_array($value) && !empty($value))
        $value = current($value);

        if(is_string($value))
        {
            if($value === 'insertId')
            $return = ($type === 'insert')? true:false;

            elseif($value === 'rowCount' && in_array($type,['select','show','insert','update','delete'],true))
            $return = true;

            elseif(array_key_exists($value,static::$config['output']['all']) && ($type === 'select' || $type === 'show'))
            {
                if(empty(static::$config['output']['all'][$value]['onlySelect']) || $type === 'select')
                $return = true;
            }
        }

        return $return;
    }


    // parseDsn
    // parse une string dsn
    // le charset est ajouté à la fin du dsn de retour
    public static function parseDsn(string $dsn,string $charset):?array
    {
        $return = null;

        if(strlen($dsn) && strlen($charset))
        {
            $parse = parse_url($dsn);

            if(is_array($parse) && !empty($parse['scheme']) && !empty($parse['path']))
            {
                $parse['driver'] = $parse['scheme'];
                $parse['dsn'] = $dsn;
                $parse['charset'] = $charset;

                if(!Base\Str::isEnd($charset,$return['dsn']))
                $parse['dsn'] .= ';charset='.$charset;

                foreach(Base\Str::explode(';',$parse['path'],null,true,true) as $x)
                {
                    $keyValue = Base\Str::explodeKeyValue('=',$x,true,true);
                    if(!empty($keyValue))
                    $parse = Base\Arr::append($parse,$keyValue);
                }

                if(empty($parse['port']))
                $parse['port'] = static::defaultPort();

                if(!empty($parse['host']) && !empty($parse['dbname']))
                $return = $parse;
            }
        }

        return $return;
    }


    // parseDataType
    // retourne le datatype pour la valeur selon son type
    public static function parseDataType($value):?int
    {
        $return = null;

        if(is_int($value))
        $return = \PDO::PARAM_INT;

        elseif(is_bool($value))
        $return = \PDO::PARAM_BOOL;

        elseif(is_string($value))
        $return = \PDO::PARAM_STR;

        elseif(is_float($value))
        $return = \PDO::PARAM_STR;

        elseif($value === null)
        $return = \PDO::PARAM_NULL;

        return $return;
    }


    // parseFetch
    // retourne le code numérique pour fetch
    // true retourne le code par défaut
    public static function parseFetch($value):?int
    {
        $return = null;

        if(is_string($value) && array_key_exists($value,static::$config['fetch']))
        $return = static::$config['fetch'][$value];

        elseif(is_int($value))
        $return = $value;

        return $return;
    }


    // output
    // retourne le tableau de configuration pour output
    // true retourne le output par défaut pour le type de requête
    public static function output(string $type,$value):?array
    {
        $return = null;

        if(static::isOutput($type,$value))
        {
            if($value === true)
            {
                if($type === 'select' || $type === 'show')
                $value = (!empty(static::$config['output']['default'][$type]))? static::$config['output']['default'][$type]:null;

                elseif($type === 'insert')
                $value = 'insertId';

                elseif($type === 'update' || $type === 'delete')
                $value = 'rowCount';

                else
                $value = 'statement';
            }

            if(is_array($value) && !empty($value))
            {
                if(array_key_exists('beforeAfter',$value))
                unset($value['beforeAfter']);

                $replace = Base\Arr::spliceFirst($value);
                $value = (count($value))? current($value):null;
            }

            if($value === null)
            $value = 'statement';

            if(is_string($value) && array_key_exists($value,static::$config['output']['all']))
            {
                $return = static::$config['output']['all'][$value];
                $return['type'] = $value;

                if(!empty($replace))
                $return = Base\Arr::replace($return,$replace);

                if(array_key_exists('fetch',$return))
                {
                    $fetch = static::parseFetch($return['fetch']);

                    if($fetch !== null || !is_string($return['fetch']))
                    $return['fetch'] = $fetch;
                }

                if(array_key_exists('arg',$return))
                $return['arg'] = (array) $return['arg'];
            }
        }

        return $return;
    }


    // outputKey
    // permet de placer la clé du tableau multidimensionnel à partir d'un nom ou index de colonne
    // supporte des tableaux avec objet
    public static function outputKey($key,array $return):array
    {
        if(is_scalar($key) && !empty($return))
        {
            $current = current($return);

            if(is_object($current))
            {
                $array = $return;
                foreach ($array as $k => $v)
                {
                    $array[$k] = (array) $v;
                }

                if(is_int($key))
                $array = Base\Column::keyFromIndex($key,$array);
                else
                $array = Base\Column::keyFrom($key,$array);

                $keys = array_keys($array);
                $values = array_values($return);

                if(count($keys) === count($values))
                $return = array_combine($keys,$values);

                else
                static::throw('keysAndValuesCountNotTheSame');
            }

            else
            {
                if(is_int($key))
                $return = Base\Column::keyFromIndex($key,$return);
                else
                $return = Base\Column::keyFrom($key,$return);
            }
        }

        return $return;
    }


    // selectLimit
    // une valeur numérique limit peut être ajouté dans le tableau input sql si configuré
    // par exemple pour une requête select assoc, limit 1 est ajouté
    // offset permet de preprend une ou plusieurs entrées au tableau, par exemple si what n'est pas censé être dans le tableau
    public static function selectLimit($output,array $return,int $offset=0):array
    {
        $output = static::output('select',$output);

        if(!empty($output) && array_key_exists('selectLimit',$output))
        {
            $array = ($offset > 0)? Base\Arr::unshift($return,$offset):$return;
            $makeParse = Syntax::makeParse('select','limit',$array);

            if($makeParse === null)
            $return['limit'] = $output['selectLimit'];
        }

        return $return;
    }


    // defaultPort
    // retourne le port par défaut pour l'engin sql
    public static function defaultPort():int
    {
        return static::$config['port'];
    }


    // allDrivers
    // retourne les drivers pdo disponibles
    public static function allDrivers():array
    {
        return \PDO::getAvailableDrivers();
    }


    // setDefaultHistory
    // change la valeur par défaut d'history dans option avant la création de l'objet db
    public static function setDefaultHistory(bool $value):void
    {
        static::$config['option']['history'] = $value;

        return;
    }
}
?>