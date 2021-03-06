<?php
namespace AuthorBooks\Tests;
use SQLBuilder\Raw;
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\AuthorBookSchema;
use DateTime;

class BookModelTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new AuthorSchema,
            new BookSchema,
            new AuthorBookSchema,
        ];
    }

    public function testImmutableColumn()
    {
        $b = new Book ;
        // $b->autoReload = false;
        $ret = $b->create(array( 'isbn' => '123123123' ));

        $this->assertResultSuccess($ret);

        $ret = $b->update(array('isbn'  => '456456' ));
        $this->assertResultFail($ret, 'Should not update immutable column');

        $this->successfulDelete($b);
    }


    /**
     * TODO: Should we validate the field ? think again.
     *
     * @expectedException PDOException
     */
    public function testUpdateUnknownColumn()
    {
        $b = new Book;
        // Column not found: 1054 Unknown column 'name' in 'where clause'
        $b->load(array('name' => 'LoadOrCreateTest'));
    }

    public function testFlagHelper()
    {
        $b = new Book ;
        $ret = $b->create([ 'title' => 'Test Book' ]);
        $this->assertResultSuccess($ret);

        $schema = $b->getSchema();
        ok($schema);

        $cA = $schema->getColumn('is_hot');
        $cB = $schema->getColumn('is_selled');
        ok($cA);
        ok($cB);

        $ret = $b->update([ 'is_hot' => true ]);
        $this->assertResultSuccess($ret);

        $ret = $b->update([ 'is_selled' => true ]);
        $this->assertResultSuccess($ret);

        $ret = $b->delete();
        $this->assertResultSuccess($ret);
    }

    public function testTraitMethods() {
        $b = new Book ;
        $this->assertSame(['link1', 'link2'], $b->getLinks());
        $this->assertSame(['store1', 'store2'], $b->getStores());
    }

    public function testInterface() {
        $this->assertInstanceOf('TestApp\ModelInterface\EBookInterface', new Book);
    }

    public function testLoadOrCreate() {
        $results = array();
        $b = new Book ;

        $ret = $b->create(array( 'title' => 'Should Not Load This' ));
        result_ok( $ret );
        $results[] = $ret;

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        result_ok( $ret );
        $results[] = $ret;

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id, $b->id, 'is the same ID');
        $results[] = $ret;


        $b2 = new Book ;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id,$b2->id);
        $results[] = $ret;

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        result_ok($ret);
        ok($b2);
        ok($id != $b2->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3 = new Book ;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        result_ok($ret);
        ok($b3);
        ok($id != $b3->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3->delete();

        foreach( $results as $r ) {
            $book = new Book;
            $book->load($r->id);
            if ($book->id) {
                $book->delete();
            }
        }
    }

    public function testTypeConstraint()
    {
        $book = new Book ;
        $ret = $book->create(array( 
            'title' => 'Programming Perl',
            'subtitle' => 'Way Way to Roman',
            'view' => '""',  /* cast this to null or empty */
            // 'publisher_id' => NULL,  /* cast this to null or empty */
        ));
        $this->assertResultSuccess($ret);
    }


    /**
     * @rebuild false
     */
    public function testRawSQL()
    {
        $n = new Book ;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );

        $ret = $n->update(array( 
            'view' => new Raw('view + 1')
        ), array('reload' => 1));

        ok( $ret->success );
        is( 1 , $n->view );

        $n->update(array( 
            'view' => new Raw('view + 3')
        ), array('reload' => 1));
        is( 4, $n->view );
    }


    public function testDateTimeValue()
    {
        $date = new DateTime;
        $book = new Book;
        $ret = $book->create([ 'title' => 'Create With Time' , 'view' => 0, 'published_at' => $date ]);
        $this->assertResultSuccess($ret);
        $this->assertInstanceOf('DateTime', $book->published_at);
        $this->assertEquals('00-00-00 00-00-00',$date->diff($book->published_at)->format('%Y-%M-%D %H-%I-%S'));
    }




    public function testCreateOrUpdateOnTimestampColumn()
    {
        $date = new DateTime;

        $book = new Book;
        $ret = $book->create([ 'title' => 'Create With Time' , 'view' => 0, 'published_at' => $date ]);
        $this->assertResultSuccess($ret);
        $this->assertCount(1, new BookCollection);

        $id = $book->id;
        $this->assertNotNull($id);

        $ret = $book->load([ 'published_at' => $date ]);
        $this->assertResultSuccess($ret);

        $ret = $book->createOrUpdate([ 'title' => 'Update With Time' , 'view' => 0, 'published_at' => $date ], [ 'published_at' ]);
        $this->assertResultSuccess($ret);
        $this->assertCount(1, new BookCollection);
        

        $this->assertEquals('Update With Time', $book->title);
        $this->assertEquals($id, $book->id);
    }


    /**
     * @rebuild false
     */
    public function testZeroInflator()
    {
        $b = new Book ;
        $ret = $b->create(array( 'title' => 'Create X' , 'view' => 0 ));
        $this->assertResultSuccess($ret);

        ok( $b->id );
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        $this->assertResultSuccess($ret);
        ok( $b->id );
        is( 0 , $b->view );

        // test incremental
        $ret = $b->update(array( 'view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is( 1,  $b->view );

        $ret = $b->update(array( 'view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is( 2,  $b->view );

        $ret = $b->delete();
        $this->assertResultSuccess($ret);
    }

    /**
     * @rebuild false
     */
    public function testGeneralInterface() 
    {
        $a = new Book;
        ok($a);
        ok( $a->getQueryDriver('default') );
        ok( $a->getWriteQueryDriver() );
        ok( $a->getReadQueryDriver() );
    }
}

