# Demo

<code>
	
$dsn = 'mysql:host=127.0.0.1;port=3306;dbname=house';

$manage = new pdoHandle(new PDO($dsn, 'root', 'root'));

</code>

#select

<code>
	
$manage->getRow('SELECT * FROM test');

$manage->getRow('SELECT * FROM test WHERE id=:id', [':id'=>10]);

$manage->getRow('SELECT * FROM test WHERE id=:id', [':id'=>10], [':id'=>$manage::PARAM_INT);

$manage->table('test')->all();

$manage->table('test')->field('id,name')->limit(1)->order('id DESC')->all();

$manage->table('test')->where('id>100')->find();

</code>

#create

<code>

$manage->table('test')->create(['name'=>1]);

</code>

#update

<code>
	
$manage->table('test')->update(['name'=>'test2']);

$manage->table('test')->where('id=5')->update(['content'=>'test', 'name'=>'test2']);

$manage->table('test')->where('id=:id', [':id'=>3])->update(['content'=>'test', 'name'=>'test2']);

$manage->table('test')->where('id=:id', [':id'=>2], [':id'=>$manage::PARAM_INT])->update(['content'=>'test', 'name'=>'test2']);

</code>

#delete
<code>
	
$manage->table('test')->delete();

$manage->table('test')->where('id=2')->delete();

$manage->table('test')->where('id=:id', [':id'=>3])->delete();

$manage->table('test')->where('id=:id', [':id'=>3], [':id'=>$manage::PARAM_INT])->delete();

</code>