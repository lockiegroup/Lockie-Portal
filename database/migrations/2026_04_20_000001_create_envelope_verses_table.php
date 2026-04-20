<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('envelope_verses', function (Blueprint $table) {
            $table->id();
            $table->string('label', 10);
            $table->json('lines');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('envelope_verses')->insert([
            ['label' => 'V1',  'sort_order' => 1,  'lines' => json_encode(['All things come from You,', 'O Lord and of Your Own do', 'we give You', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V2',  'sort_order' => 2,  'lines' => json_encode(['"Give back some of God\'s', 'gifts to God, that you may', 'safely enjoy the rest."', 'St. John Henry Newman', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V3',  'sort_order' => 3,  'lines' => json_encode(['The Lord blesses His', 'people with peace.', 'Psalms 29 V11', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V4',  'sort_order' => 4,  'lines' => json_encode(['In Thanksgiving to God', 'and for the work of', 'His Church', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V5',  'sort_order' => 5,  'lines' => json_encode(['Trust in the Lord with all', 'your heart.', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V6',  'sort_order' => 6,  'lines' => json_encode(['Blessed are the pure in', 'heart for they shall', 'see God.', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V7',  'sort_order' => 7,  'lines' => json_encode(['What return can I make', 'to the Lord for all His', 'goodness to me.', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V8',  'sort_order' => 8,  'lines' => json_encode(['OUR GIFT TO GOD AND', 'HIS CHURCH', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V9',  'sort_order' => 9,  'lines' => json_encode(['For the support of our Church,', 'Schools and Priests.', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V10', 'sort_order' => 10, 'lines' => json_encode(['My Gift to God', 'and His Church', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V11', 'sort_order' => 11, 'lines' => json_encode(['Those that hope in the Lord', 'will renew their strength.', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V12', 'sort_order' => 12, 'lines' => json_encode(['I am the Good Shepherd –', 'the good shepherd giveth his life', 'for his sheep.', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V13', 'sort_order' => 13, 'lines' => json_encode(['If unable to come, please remember', 'the work is going on and needs your', 'support. Kindly fill up the envelopes', 'for the Sundays missed and bring', 'them next time you are present', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V14', 'sort_order' => 14, 'lines' => json_encode(['"For God loveth a cheerful giver"', '(2 Cor, 9 v. 7)', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V15', 'sort_order' => 15, 'lines' => json_encode(['"Freely you have received', 'Freely give ..."', 'St. Matt. 10 v. 8.', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V16', 'sort_order' => 16, 'lines' => json_encode(['Faith comes from hearing the', 'message, and the message is', 'heard through the word of', 'Christ.', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V17', 'sort_order' => 17, 'lines' => json_encode(['"The Lord Jesus Himself said', 'happiness lies more in giving', 'than in receiving"', 'Acts 20, 35.', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V18', 'sort_order' => 18, 'lines' => json_encode(['MY WEEKLY OFFERING', '', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V19', 'sort_order' => 19, 'lines' => json_encode(['"Where your treasure is, there', 'will your heart be also."', 'Matt. 6, v. 21', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V20', 'sort_order' => 20, 'lines' => json_encode(['OUR WEEKLY', 'OFFERING TO GOD', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V21', 'sort_order' => 21, 'lines' => json_encode(['Weekly Offering to God', 'and for the Work', 'of His Church', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V22', 'sort_order' => 22, 'lines' => json_encode(['For whenever you eat this bread', 'and drink this cup – you', 'proclaim the Lord\'s death until', 'he cometh.', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V23', 'sort_order' => 23, 'lines' => json_encode(['The Lord shall be unto thee an', 'everlasting light.', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
            ['label' => 'V24', 'sort_order' => 24, 'lines' => json_encode(['Rejoice in the Lord always.', '', '', '', '', '', '', '']), 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('envelope_verses');
    }
};
