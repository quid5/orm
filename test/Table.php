<?php
declare(strict_types=1);

/*
 * This file is part of the QuidPHP package.
 * Website: https://quidphp.com
 * License: https://github.com/quidphp/test/blob/master/LICENSE
 */

namespace Quid\Test\Orm;
use Quid\Orm;
use Quid\Main;
use Quid\Base;

// table
// class for testing Quid\Orm\Table
class Table extends Base\Test
{
	// trigger
	public static function trigger(array $data):bool
	{
		// prepare
		$db = Orm\Db::inst();
		$table = 'ormTable';
		assert($db->truncate($table) instanceof \PDOStatement);
		assert($db->inserts($table,['id','active','name_en','dateAdd','userAdd','dateModify','userModify','name_fr','email','date'],[1,1,'james',10,11,12,13,'james_fr','james@james.com',123312213],[2,2,'james2',20,21,22,23,'james_fr','james@james.com',123312213]) === [1,2]);
		$tables = $db->tables();
		$tb = $db[$table];
		$tb->rowsUnlink();
		assert($tb[1] instanceof Orm\Row);
		foreach ($tb as $key => $value) { }
		assert(count($tb) === 1);
		$tb2 = $db['ormCol'];
		$tb3 = $db['ormDb'];
		$user = $db['user'];
		$logSql = $db['logSql'];
		assert($tb['name_[lang]']->name() === 'name_en');
		assert($tb['id']('name') === 'id');

		// construct

		// toString

		// onColsLoad

		// onMakeAttr

		// onCheckAttr

		// onTruncated

		// toArray
		assert($tb->toArray() === [1=>'james',2=>'james2']);

		// cast
		assert($tb->_cast() === $table);

		// offsetGet
		assert($tb->offsetGet(1) === $tb[1]);
		assert($tb->offsetGet('id') instanceof Orm\Col);
		assert($tb->offsetGet('name_[lang]') instanceof Orm\Col);

		// offsetSet

		// offsetUnset
		assert($tb->hasRow(1));
		unset($tb[1]);
		assert(!$tb->hasRow(1));

		// arr

		// isLinked
		assert($tb->isLinked());

		// alive
		assert($tb->alive());

		// shouldLogSql
		assert($tb->shouldLogSql('truncate'));
		assert(!$logSql->shouldLogSql('truncate'));

		// hasPermission
		assert($tb->hasPermission('select'));
		assert($tb->hasPermission('select','insert'));

		// checkPermission
		assert($tb->checkPermission('select') === $tb);

		// permission
		assert(count($tb->permission()) >= 10);
		assert(is_array($tb->permission('colPopup')));
		assert($tb->permission('select'));

		// isSearchable
		assert($tb->isSearchable());

		// isSearchTermValid
		assert(!$tb->isSearchTermValid(null));
		assert(!$tb->isSearchTermValid('a'));
		assert($tb->isSearchTermValid('abc'));
		assert(!$tb->isSearchTermValid([]));
		assert($tb->isSearchTermValid(['abc','bcd']));
		assert($tb->isSearchTermValid(['ab','c']));
		assert(!$tb->isSearchTermValid(['a','c']));

		// sameTable
		assert($tb->sameTable($tb[1]));

		// hasPanel
		assert($tb->hasPanel());

		// setClasse

		// classe

		// setLink

		// setName

		// name
		assert($tb->name() === $table);

		// makeAttr

		// checkAttr

		// parent
		assert($tb->parent() === 'ormDb');

		// priority
		$deep = $tables['ormRowsIndexDeep'];
		assert(is_int($tb->priority()));
		assert($tb->priority() !== $deep->priority());

		// where
		assert($tb->where() === []);
		assert($tb->where(['ok'=>'yeah']) === ['ok'=>'yeah']);
		assert($tb->where(true) === ['active'=>1,['name_en',true],['name_fr',true],['email',true],['date',true]]);
		assert(count($tb->where(['active'=>4,true])) === 10);
		assert(count($tb->where([true,'active'=>4,true])) === 10);
		assert(is_int($tb3->where()[0][2]));
		assert(count($tb3->where(['ok'=>'yeah'])) === 2);

		// filter
		assert($tb->filter() === []);

		// commonWhereFilter

		// commonWhereFilterArg

		// whereFilterTrue
		assert($tb->whereFilterTrue() === ['active'=>1,['name_en',true],['name_fr',true],['email',true],['date',true]]);
		assert($logSql->whereFilterTrue() === [['type',true],['context',true],['request',true],['userCommit',true]]);
		assert(count($tb3->whereFilterTrue()) === 1);

		// whereFilter
		assert($tb->whereFilter() === []);
		assert(is_int($tb3->whereFilter()[0][2]));

		// whereAll
		assert($tb->whereAll() === [['id','>=',1]]);

		// like
		assert($tb->like() === 'like');

		// searchMinLength
		assert($tb->searchMinLength() === 3);

		// order
		assert($tb->order() === ['id'=>'desc']);
		assert(count($tb->order(false)) === 2);
		assert($db['lang']->order(false) === ['order'=>$db['lang']['id'],'direction'=>'desc']);
		assert($tb->order('direction') === 'desc');

		// limit
		assert($tb->limit() === 20);

		// default
		assert(count($tb->default()) === 2);

		// status
		assert(count($tb->status()) >= 18);

		// engine
		assert($tb->engine() === 'MyISAM');

		// autoIncrement
		assert($tb->autoIncrement() === 3);

		// collation
		assert($tb->collation() === 'utf8mb4_general_ci');

		// primary
		assert($tb->primary() === 'id');

		// isColLinked

		// hasCol
		assert($tb->hasCol('id','name_en'));
		assert(!$tb->hasCol('id','name'));

		// isColsReady
		assert($tb->isColsReady());

		// isColsEmpty
		assert(!$tb->isColsEmpty());

		// setColsReady

		// colsNew
		assert($tb->colsNew() instanceof Orm\Cols);

		// colsCount
		assert($tb->colsCount() === 12);
		assert($tb->colsCount(false) === 12);

		// colsLoad

		// colMake

		// colAttr
		assert($tb2->colAttr('myRelation') === ['relation'=>['test',3,4,9=>'ok']]);

		// cols
		assert(count($tb->cols()) === 12);
		assert($tb->cols()->gets('id','dateAdd')->count() === 2);
		assert($tb->cols('id','name_[lang]')->count() === 2);

		// col
		assert($tb->col('dateModify') instanceof Orm\Col);
		assert($tb->col($tb->col('dateModify')) instanceof Orm\Col);
		assert($tb->col($tb[1]['id']) instanceof Orm\Col);
		assert($tb->col(0) === $tb->col('id'));
		assert($tb->col([1000,0]) === $tb->col('id'));
		assert($tb->col(['Lol','id']) === $tb->col('id'));

		// colPattern
		assert($tb->colPattern('name')->name() === 'name_en');
		assert($tb->colPattern('name_en')->name() === 'name_en');
		assert($tb->colPattern('id')->name() === 'id');
		assert($tb->colPattern('name','fr')->name() === 'name_fr');
		assert($tb->colPattern('name','de') === null);

		// colActive
		assert($tb->colActive()->name() === 'active');
		assert($logSql->colActive() === null);

		// colKey
		assert($tb->colKey()->name() === 'id');

		// colName
		assert($tb->colName()->name() === 'name_en');
		assert($tb->colName('fr')->name() === 'name_fr');

		// colContent
		assert($tb->colContent()->name() === 'content_en');

		// isRowLinked

		// hasRow
		assert($tb->hasRow(1));
		assert(!$tb->hasRow(2));
		$tb[2]->terminate();
		assert($tb->hasRow(2));
		assert($tb->rows()->clean() instanceof Orm\Rows);

		// isRowsEmpty
		assert(!$tb->isRowsEmpty());
		assert(!$tb->isRowsEmpty(true));

		// isRowsNotEmpty
		assert($tb->isRowsNotEmpty());
		assert($tb->isRowsNotEmpty(true));

		// rowsNew
		assert($tb->rowsNew() instanceof Orm\Rows);

		// rowsCount
		assert($tb->emptyCache() === $tb);
		assert($tb->rowsCount() === 1);
		assert($tb->rowsCount(true,true) === 2);
		assert($tb->rowsCount() === 1);
		assert($tb->allCache()['Quid\Orm\Table::rowsCount'] === 2);
		assert($tb->rowsCount() === 1);

		// rowsLoad
		assert($tb->rows()->count() === 1);
		assert($tb->rowsLoad() instanceof Orm\Rows);
		assert($tb->rows()->count() === 2);
		assert($tb->rowsLoad() instanceof Orm\Rows);

		// rowsValue
		assert($tb->rowsValue(2) === [0=>2]);
		assert($tb->rowsValue(true) === [0=>1]);

		// rows
		assert($tb->rows(true) instanceof Orm\Rows);
		assert($tb->rows(false)->isEmpty());
		assert($tb->rows() instanceof Orm\Rows);
		assert($tb->rows(2,1,3)->count() === 2);
		assert($tb->rows(2,1) !== $tb->rows());
		$count = count($db->history()->keyValue());
		$tb->rows(2,1);
		assert(count($db->history()->keyValue()) === $count);

		// rowsMakeIds

		// rowsRefresh
		$count = count($db->history()->keyValue());
		$tb->rowsRefresh(2,1);
		assert(count($db->history()->keyValue()) === ($count + 2));
		$tb->rowsUnlink(2);
		$tb->rowsRefresh(2,1);
		assert(count($db->history()->keyValue()) === ($count + 4));

		// rowsIn
		$tb->rowsUnlink(2);
		assert($tb->rows()->primaries() === [1]);
		assert($tb->rowsIn(2,1)->count() === 1);
		assert($tb->rows(2,1)->count() === 2);

		// rowsInRefresh
		$count = count($db->history()->keyValue());
		assert($tb->rowsIn(2,1)->count() === 2);
		assert(count($db->history()->keyValue()) === $count);
		assert($tb->rowsInRefresh(2,1)->count() === 2);
		assert(count($db->history()->keyValue()) === ($count + 1));

		// rowsOut
		assert($tb->rowsOut(2,1)->count() === 0);
		$tb->rowsUnlink(2,1);
		assert($tb->rowsOut(2,1)->count() === 2);
		assert($tb->rowsOut(2,1)->count() === 0);

		// rowsDelete
		assert($tb->rows(1,2,3,4)->count() === 2);
		assert($tb->rowsDelete(1)->count() === 1);
		assert($tb->rowsDelete()->count() === 0);
		assert($tb->rows(1,2,3,4)->count() === 0);
		assert($db->inserts($table,['id','active','name_en','dateAdd','userAdd','dateModify','userModify','name_fr','email','date'],[1,1,'james',10,11,12,13,'james_fr','james@test.com',121221121],[2,2,'james2',20,21,22,23,'james_fr','james@test.com',121221121]) === [1,2]);
		assert($tb->rowsLoad()->count() === 2);

		// rowsUnlink
		assert($tb->rows(1,2,3,4)->count() === 2);
		assert($tb->rows()->primaries() === [1,2]);
		$tb->rowsUnlink(1);
		assert($tb->rows()->count() === 1);
		assert($tb->rows(1,2,3,4)->count() === 2);
		$tb->rowsUnlink();

		// rowsVisible
		assert($tb->rowsVisible()->isEmpty());

		// rowsVisibleOrder
		assert($tb->rowsVisibleOrder()->isEmpty());

		// rowsClass
		assert(is_a($tb->rowsClass(),Orm\Rows::class,true));

		// rowClass
		assert(is_a($tb->rowClass(),Orm\Row::class,true));

		// rowMake

		// rowValue
		assert($tb->rowValue(['active'=>1]) === 1);
		assert($tb->rowValue(true) === 1);

		// row
		assert($tb->row('1') instanceof Orm\Row);
		assert($tb->row(1) instanceof Orm\Row);
		assert($tb->row(25) === null);
		assert($tb->row(['id'=>1]) instanceof Orm\Row);
		assert($tb->row(['id'=>[1,2]]) instanceof Orm\Row);

		// rowVisible
		assert($tb->rowVisible('1') instanceof Orm\Row);

		// rowRefresh
		$count = count($db->history()->keyValue());
		$tb->rowRefresh(1);
		$tb->rowRefresh(1);
		assert(count($db->history()->keyValue()) === ($count + 2));

		// rowIn
		assert($tb->rowIn(1) instanceof Orm\Row);
		assert($tb->rowIn(2) === null);
		assert($tb->row(2) instanceof Orm\Row);

		// rowInRefresh
		$count = count($db->history()->keyValue());
		assert($tb->rowInRefresh(2) instanceof Orm\Row);
		assert(count($db->history()->keyValue()) === ($count + 1));
		$tb->rowsUnlink(2);

		// rowOut
		assert($tb->rowOut(2) instanceof Orm\Row);
		assert($tb->rowOut(2) === null);
		$tb->rowsUnlink(2);

		// checkRow
		assert($tb->checkRow(1) instanceof Orm\Row);

		// select
		assert($tb->select(true)->id() === 1);

		// selectPrimary
		assert($tb->selectPrimary(2) === 2);
		assert($tb->selectPrimary(200000) === null);

		// selects
		assert($tb->selects(['active'=>false])->isEmpty());

		// selectPrimaries
		assert($tb->selectPrimaries([1,2,1000]) === [1,2]);

		// grab
		assert($tb->grab(['id'=>23])->isEmpty());

		// grabVisible
		assert($tb->grabVisible() instanceof Orm\Rows);

		// insert
		$insert = $tb->insert(['date'=>time(),'active'=>null,'name_fr'=>'nomFr']);
		assert($insert['active']->value() === null);
		assert(is_int($insert['dateAdd']->value()));
		assert($insert->isLinked());
		assert($insert['name_en']->value() === 'LOL');
		assert($insert->id() === 3);
		$insert2 = $tb->insert(['date'=>time(),'name_en'=>'LOL2','name_fr'=>'nomFr']);
		assert($insert2['name_en']->value() === 'LOL2');
		assert($insert2->id() === 4);
		$x = $tb->insert(['date'=>time(),'name_en'=>'test','name_fr'=>'james'],['default'=>true]);
		assert($x->delete() === 1);
		$pre = $tb->insert(['date'=>'03-03-17','name_en'=>'test','name_fr'=>'nomFr'],['com'=>true,'preValidate'=>true,'default'=>true]);
		assert(strlen($tb->db()->com()->flush()) === 285);
		$idInsert = $tb->insert(['id'=>999,'date'=>'03-03-17','name_fr'=>'test']);
		assert($idInsert->primary() === 999);
		assert($tb->autoIncrement(false) === 1000);
		assert($idInsert->delete() === 1);
		assert($tb->alterAutoIncrement() === $tb);

		// insertCom

		// insertPreValidate

		// insertValidate

		// insertAfter

		// insertOnCommitted

		// label
		$tb2 = $tables['ormDb'];
		assert($tb2->label() === 'Le nom de la table');
		assert($tb->label() === 'Super Orm En');
		assert($tb->label(null,'fr') === 'Super Orm Fr');
		assert($tb->label('%:') === 'Super Orm En:');
		assert($tb->label('%:','fr') === 'Super Orm Fr:');
		assert($tb->label(3,'fr') === 'Sup');

		// description
		assert($tb2->description() === 'ok/Description table');
		assert($tb->description() === 'Super Orm Desc En');
		assert($tb->description(null,null,'fr') === null);
		assert($tb2->description('%:') === 'ok/Description table:');

		// allowsRelation
		assert($tb->allowsRelation());

		// relation
		assert($tb->relation() instanceof Orm\TableRelation);

		// segment
		assert(current($tb->segment('[name_%lang%] [id] [id] [dateAdd]',false,null,['id'=>'asc'])) === 'james 1 1 10');
		assert(current($tb->segment('[name_%lang%] [id] [id] [dateAdd]',true,null,['id'=>'asc'])) !== 'james 1 1 10');

		// keyValue
		assert($tb->keyValue(0,['james','name_en']) === [2=>'james2',1=>'james',3=>'LOL',4=>'LOL2']);
		assert(is_string($tb->keyValue(0,['james','dateAdd'],true,3)[3]));
		assert($tb->keyValue(0,['james','name_[lang]'],true)[1] === 'james');

		// search
		assert($tb->search('james2') === [2]);
		assert($tb->search('james',null,[['id'=>'asc']],['method'=>'like']) === [1,2]);
		assert($tb->search([['james','james2']],null,null,['method'=>'or|like']) === [2,1]);
		assert($tb->search('james mes2',null,null,['method'=>'like']) === [2]);
		assert($tb->search('james + mes2',null,null,['method'=>'like','searchSeparator'=>'+']) === [2]);
		$rows = $tb->search('james mes2',null,null,['output'=>'rows','what'=>'*']);
		assert($rows instanceof Orm\Rows);
		$rows->unlink();

		// truncate
		assert($db['ormCol']->truncate() === true);

		// truncateAfter

		// deleteTrim
		assert($tb->deleteTrim(1000,false) === null);
		assert($tb->deleteTrim(4,false) === 0);
		assert($tb->deleteTrim(3,false) === 1);

		// reservePrimary
		assert($tb->reservePrimary() === 5);

		// alter

		// alterAutoIncrement
		$tb->alterAutoIncrement(1000);
		$row = $tb->insert(['date'=>time(),'name_fr'=>'welll']);
		assert($row->primary() === 1000);
		$tb->alterAutoIncrement(0);
		$row->unlink();

		// addKey

		// addCol

		// drop

		// dropKey

		// total
		assert($tb->total()['cell'] === 36);
		assert($tb->total(true)['cell'] > 36);

		// info
		assert(count($tb->info()) === 4);
		assert($tb->info()['row'] === [1,3,4]);

		// sql
		assert($tb->sql(['search'=>'what','page'=>2,'limit'=>10]) instanceof Orm\Sql);
		assert($tb->sql(['where'=>['active'=>1],'page'=>2,'limit'=>1])->trigger()->isCount(1));
		assert($tb->sql(['where'=>[['active','in',[1,2,3,4]]],'page'=>1,'limit'=>10])->trigger()->isCount(3));
		assert($tb->sql(['order'=>'james','direction'=>'desc'])->get('order')[1] === ['id','asc']);
		assert(count($tb->sql(['order'=>'id','direction'=>'desc'])->get('order')) === 1);

		// hierarchy

		// sourceRewind

		// sourceOne
		$i = 0;
		assert($tb->sourceOne(true,true,$i)['id'] === 2);
		$i = 1;
		assert($tb->sourceOne(true,true,$i)['id'] === 3);
		$target = new class(true) extends Main\File implements Main\Contract\Import {
			use Main\File\_csv;
			public static $config = [];
			public function __construct(...$args)
			{
				static::__config();
				parent::__construct(...$args);
			}
		};
		$import = Main\Importer::newOverload($tb,$target);
		$import->set('name_en',1);
		$import->set('email',0);
		assert(count($import->trigger()) === 2);
		assert($target->lines()[1][0] === 'default@def.james');

		// targetInsert

		// targetUpdate

		// targetDelete

		// targetTruncate

		// isIgnored
		assert(!Orm\Table::isIgnored());

		// configReplaceMode

		// attr
		assert(count($tb->attr()) === 20); // un de plus car @app n'est pas enlevé
		assert($tb->attr('test') === 'ok');
		assert($tb->attr('key') === ['key',0]);
		assert($tb->attrCall('test') === 'ok');
		assert($tb->attrNotEmpty('test'));

		// dbAccess
		$tb[1];
		assert($tb->hasDb());
		assert($tb->checkDb() === $tb);
		assert($tb->checkLink() === $tb);
		assert($tb->db() instanceof Orm\Db);
		assert($tb->isLinked());
		assert($tb->checkLink() === $tb);

		// cleanup
		assert($db->truncate($table) instanceof \PDOStatement);

		return true;
	}
}
?>