<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\Concerns\InteractsWithExceptionHandling;

use Tests\TestCase;
use App\Models\{User, Vote, VoteItem};
use Illuminate\Support\Str;

class VoteItemsCrudTest extends TestCase
{
    use InteractsWithExceptionHandling;

    protected static $wasSetup = false;
    protected static $loggedUser = null;

    public function setUp(): void
    {
        parent::setUp();
        if ( ! self::$wasSetup) {
            // Regenerate structure / fresh data only once at first test
            \Artisan::call(' migrate:fresh --seed');
            Artisan::call('config:clear');
            $databaseName = \DB::connection()->getDatabaseName();
            $result = Str::endsWith($databaseName, 'HttpTesting');
            if ( ! $result) {
                die('Invalid database "' . $databaseName . '" connected ');
            }
            self::$wasSetup   = true;
            self::$loggedUser = User::factory(User::class)->create();
        }
    }

    // 1) TO ADD VOTE_AND ADD_3_VOTE ITEMS_WITH ONLY 1 CORRECT AND CHECK IT
    public function test_1_VoteAddedWith3VoteItemsAndReadWith1Correct()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => true,
                      'ordering'   => 1000,
            ]); // model only in memory
        $newVoteItemModelData3 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 1001,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData3->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        // Read all voteItems by $vote->id to check inserted data on prior steps
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->get(route('voteItems.voteItemsGet', $vote->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200

        $correctVoteItemsCount = 0;
        $this->assertEquals(3, count($response->original['voteItems']), '11 : number of vote items is invalid');
        $index = 0;
        foreach ($response->original['voteItems'] as $voteItem) {
            $this->assertEquals($index + 999, $voteItem->ordering, '12 : invalid ordering of vote item');
            if ($voteItem->is_correct) {
                $correctVoteItemsCount++;
            }
            $index++;
        }
        $this->assertEquals(1, $correctVoteItemsCount, '13 : Must be only 1 correct vote item');

    } // 1: VoteAdded3VoteItemsAndReadWith1Correct


    // 2) TO ADD VOTE_AND ADD_3_VOTE ITEMS_WITH ONLY 1 CORRECT AND CHECK IT
    public function test_2_VoteAddedWith3VoteItemsAndUpdatedWithCorrectReadWith1Correct()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => true,
                      'ordering'   => 1000,
            ]); // model only in memory
        $newVoteItemModelData3 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 1001,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData3->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        // Read all voteItems by $vote->id to and update rows and check them on next steps
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->get(route('voteItems.voteItemsGet', $vote->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200

        // Check Assert
        $correctVoteItemsCount = 0;
        $this->assertEquals(3, count($response->original['voteItems']), '21 : number of vote items is invalid');
        $index = 0;

        foreach ($response->original['voteItems'] as $voteItem) {
            $childItemUpdated = [
                'name'       => $voteItem->name . ' Updated # ' . $index,
                'ordering'   => 10000 + $index,
                'is_correct' => true
            ];
            $response         = $this
                ->actingAs(self::$loggedUser, 'api')
                ->put(route('voteItems.voteItemsUpdate', ['vote_id'=>$vote->id, 'vote_item_id'=>$voteItem->id]), $childItemUpdated);
            $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_UPDATED);  // 205
            $index++;
        }
        $index = 0;
        // Read all voteItems by $vote->id to check updated data on prior steps
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->get(route('voteItems.voteItemsGet', $vote->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200

        foreach ($response->original['voteItems'] as $voteItem) {
            $this->assertEquals($index + 10000, $voteItem->ordering, '23 : invalid ordering of vote items');
            if ($voteItem->is_correct) {
                $correctVoteItemsCount++;
            }
            $index++;
        }
        $this->assertEquals(1, $correctVoteItemsCount, '24 : Must be 3 correct vote item');
    }  // 2: VoteAddedWith3VoteItemsAndUpdatedWithCorrectReadWith1Correct


    // 3) TO ADD VOTE_AND ADD_1_VOTE ITEM_WITH WRONG DATA - MUST RAISE VALIDATION ERRORS
    public function test_3_NegativeVoteAddedAndFailureToStoreVoteItemWithInvalidData()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'name' => '',
                      'is_correct' => null,
                      'ordering'   => -999,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST);  // 400
    } // 3: NegativeVoteAddedAndFailureToStoreVoteItemWithInvalidData


    // 4) TO ADD VOTE_AND ADD_3_VOTE ITEMS_WITH AND FAILURE FIND THEM WITH NEGATIVE VOTE_ID
    public function test_4_NegativeVoteAddedWith3VoteItemsAndFailureNotFound()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => true,
                      'ordering'   => 1000,
            ]); // model only in memory
        $newVoteItemModelData3 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 1001,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData3->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')  // Vote Items by negative vote will not be found
            ->get(route('voteItems.voteItemsGet', -$vote->id), []);
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200
        $this->assertEquals(0, count($response->original['voteItems']), '41 : number of vote items must be 0');

    } // 4: NegativeVoteAddedWith3VoteItemsAndFailureNotFound


    // 5) TO ADD VOTE_AND ADD_3_VOTE ITEMS_WITH ONLY 1 CORRECT AND FAILURE WITH UPDATING WITH INVALID DATA
    public function test_5_NegativeVoteAddedWith3VoteItemsAndFailureToUpdateWithCorrectWithInvalidData()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => true,
                      'ordering'   => 1000,
            ]); // model only in memory
        $newVoteItemModelData3 = VoteItem::factory(Vote::class)
            ->make([
                      'creator_id' => self::$loggedUser->id,
                      'vote_id'    => $vote->id,
                      'is_correct' => false,
                      'ordering'   => 1001,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData3->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        // Read all voteItems by $vote->id to and update rows and check them on next steps
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->get(route('voteItems.voteItemsGet', $vote->id), []);

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK);  // 200

        $this->assertEquals(3, count($response->original['voteItems']), '21 : number of vote items is invalid');
        $index = 0;

        foreach ($response->original['voteItems'] as $voteItem) {
            $childItemUpdated = [
                'name'       => null,
                'ordering'   => null,
                'is_correct' => null
            ];
            $response         = $this
                ->actingAs(self::$loggedUser, 'api')
                ->put(route('voteItems.voteItemsUpdate', ['vote_id'=>$vote->id, 'vote_item_id'=>$voteItem->id]), $childItemUpdated);
            $response->assertStatus(HTTP_RESPONSE_BAD_REQUEST);  // 205
            $index++;
        }
    } // 5: NegativeVoteAddedWith3VoteItemsAndFailureToUpdateWithCorrectWithInvalidData


    // 6) TO ADD VOTE_AND ADD_2_VOTE ITEMS_AND DELETE 1 VOTE ITEM
    public function test_6_VoteItemIsDestroyed()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                'creator_id' => self::$loggedUser->id,
                'vote_id'    => $vote->id,
                'is_correct' => false,
                'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                'creator_id' => self::$loggedUser->id,
                'vote_id'    => $vote->id,
                'is_correct' => true,
                'ordering'   => 1000,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $this->assertEquals(2, count($vote->voteItems), '61 : number of vote items is invalid');


        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->delete(route('voteItemsDestroy', [ 'vote_id'=> $vote->id, 'vote_item_id'=> $vote->voteItems[0]->id ]), []);
        $vote->load('voteItems');
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_DELETED);  // 204
        $this->assertEquals(1, count($vote->voteItems), '62 : number of vote items after deleting is invalid');

    } // 6: VoteItemIsDestroyed()


    // 7) TO ADD VOTE_AND ADD_2_VOTE ITEMS AND DELETED WITH INVALID ID(NEGATIVE) - MUST RAISE VALIDATION ERRORS
    public function test_7_NegativeVoteItemIsDestroyedAsNotFound()
    {
        // Test Data Setup
        $vote                  = Vote::factory(Vote::class)->create([
            'creator_id' => self::$loggedUser->id,
            'status'     => Vote::STATUS_ACTIVE,
        ]);
        $newVoteItemModelData  = VoteItem::factory(Vote::class)
            ->make([
                'creator_id' => self::$loggedUser->id,
                'vote_id'    => $vote->id,
                'is_correct' => false,
                'ordering'   => 999,
            ]); // model only in memory
        $newVoteItemModelData2 = VoteItem::factory(Vote::class)
            ->make([
                'creator_id' => self::$loggedUser->id,
                'vote_id'    => $vote->id,
                'is_correct' => true,
                'ordering'   => 1000,
            ]); // model only in memory

        // Test Action
        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData->toArray());
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->post(route('voteItems.voteItemsStore', ['vote_id' => $vote->id]), $newVoteItemModelData2->toArray());

        // Check Assert
        $response->assertStatus(HTTP_RESPONSE_OK_RESOURCE_CREATED);  // 201

        $this->assertEquals(2, count($vote->voteItems), '71 : number of vote items is invalid');

        $response = $this
            ->actingAs(self::$loggedUser, 'api')
            ->delete(route('voteItemsDestroy', [ 'vote_id'=> $vote->id, 'vote_item_id'=> -$vote->voteItems[0]->id ]), []);
        $vote->load('voteItems');
        $response->assertStatus(HTTP_RESPONSE_NOT_FOUND);  // 404
        $this->assertEquals(2, count($vote->voteItems), '72 : number of vote items after failed deleting is invalid');

    } // 7: NegativeVoteItemIsDestroyedAsNotFound()


}
