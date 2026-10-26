<?php

namespace Database\Seeders;

use App\Models\NewsPost;
use Illuminate\Database\Seeder;

class NewsPostSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'news_id' => 'owsd-conference',
                'tag' => 'Company',
                'title' => 'PHYTOFIX LTD. Showcases Innovative Botanical Wellness Products at OWSD Southwest Zonal Conference',
                'excerpt' => 'PHYTOFIX Ltd. showcased its botanical wellness products at the 2nd Biennial Southwest Zonal Conference of the Organization for Women in Science for the Developing World (OWSD), held at Obafemi Awolowo University from 1–4 September 2026.',
                'date' => 'Sep 4, 2026',
                'content' => <<<'NEWS'
Ile-Ife, Osun State, Nigeria | September 2026

PHYTOFIX Ltd., a Nigerian phytopharmaceutical and botanical innovation company, successfully showcased its range of botanical wellness products at the 2nd Biennial Southwest Zonal Conference of the Organization for Women in Science for the Developing World (OWSD), held at Obafemi Awolowo University from 1–4 September 2026.

The conference, themed “Systems Thinking and Innovation: Transforming Challenges to Opportunities for National Development,” brought together participants interested in scientific advancement, innovation and sustainable approaches to national development.

PHYTOFIX Ltd.'s participation provided an opportunity to introduce its botanical wellness products to conference attendees, engage with potential customers and create greater awareness of the company's commitment to translating scientific research into practical botanical solutions.

Three Products Receive Positive Market Response

The company presented three products from its growing portfolio: GLUCOFIX Herbal Capsule, PhytoGold Tea and FIX-IT Herbal Capsule. The products attracted considerable interest and recorded encouraging patronage throughout the event.

The positive response provided an important opportunity for the company to connect with consumers, showcase its product range and strengthen awareness of its brand within a science-focused environment.

PHYTOFIX Ltd. is guided by its core philosophy, “Nature. Science. Impact.” The company seeks to harness Africa's rich medicinal plant biodiversity while applying scientific principles to the development of innovative, standardised and quality-driven botanical products.

Its participation at the conference reflects its interest in platforms that encourage scientific collaboration, entrepreneurship and the practical application of research to address health and wellness needs.
NEWS,
                'image' => '/first news.jpeg',
                'created_by' => 'Admin',
            ],
            [
                'news_id' => 'founder-birthday-launch',
                'tag' => 'Company',
                'title' => "Looking Ahead: Founder/CEO's 50th Birthday Celebration and Official Product Launch",
                'excerpt' => 'PHYTOFIX Ltd. is preparing for the 50th birthday celebration of its Founder and Chief Executive Officer, Dr Cecilia Oluwamodupe, alongside the official launch of the company and its innovative wellness products on 12 December 2026.',
                'date' => 'Dec 12, 2026',
                'content' => <<<'NEWS'
Building on this encouraging outing, PHYTOFIX Ltd. is preparing for a major milestone: the 50th birthday celebration of its Founder and Chief Executive Officer, Dr Cecilia Oluwamodupe, alongside the official launch of PHYTOFIX Ltd. and its innovative wellness products.

The event, themed “From Research to Legacy,” is scheduled to hold as follows:

Date: Saturday, 12 December 2026

Time: 1:00 p.m.

Venue: Bon Hotel, Ijapo Estate, Akure, Ondo State, Nigeria.

The celebration will recognise Dr Oluwamodupe's years of professional contribution and mark an important step in the company's journey to bring research-led botanical innovations to a wider audience.

The event is expected to bring together invited guests, distinguished professionals, academics, associates and other stakeholders to celebrate the occasion and witness the company's official product launch.
NEWS,
                'image' => '/second news.jpeg',
                'created_by' => 'Admin',
            ],
        ];

        NewsPost::query()
            ->whereIn('news_id', ['phytofix-labs', 'glucofix-batch', 'phytogold-infusion', 'biodiversity'])
            ->delete();

        foreach ($posts as $post) {
            NewsPost::query()->updateOrCreate(
                ['news_id' => $post['news_id']],
                $post,
            );
        }
    }
}