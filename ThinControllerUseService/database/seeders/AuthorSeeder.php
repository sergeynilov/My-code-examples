<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\Author;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use App\Library\Services\AuthorMethodsInterface;
use App;

class AuthorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $fakerImageFiles = [
            '/Init/Avatars/2.jpeg',
            '/Init/Avatars/4.jpeg',
            '/Init/Avatars/5.jpeg',
            '/Init/Avatars/JaneDoe.jpg',
            '/Init/Avatars/RedSong.jpg',
            '/Init/Avatars/black-book.jpeg',
            '/Init/Avatars/john_doe.jpg',
            '/Init/Avatars/portosandro.png',
            '/Init/Avatars/rodhen.png',
            '/Init/Avatars/shawn_hadray.jpg',
            '/Init/Avatars/shawna_hooray.jpg',
        ];


        $authorMethods = App::make(AuthorMethodsInterface::class);
        $authors = Author::factory()->count(10)->make([]);
        foreach( $authors as $author ) {
            $authorData= $author->toArray();
            $authorUploadedImageFile = Arr::random($fakerImageFiles);
            $ret = $authorMethods->store(
                requestData: $authorData,
                avatarUploadedImageFile: url($authorUploadedImageFile),
                makeValidation: false,
            );
        }

    }
}
