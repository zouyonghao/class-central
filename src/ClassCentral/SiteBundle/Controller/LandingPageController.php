<?php
/**
 * Created by PhpStorm.
 * User: dhawal
 * Date: 11/11/17
 * Time: 9:41 PM
 */

namespace ClassCentral\SiteBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class LandingPageController extends Controller
{
    public function voteBestOnlineCourses2017Action(Request $request)
    {
        $this->get('user_service')->autoLogin($request);
        return $this->render('ClassCentralSiteBundle:LandingPages:voteBestOnlineCourses2017.html.twig', array(
            'page' => 'top-courses',
            'subjects' => $this->getSubjectList()
        ));
    }
    public function yearInReview2012Action(Request $request)
    {
      $this->get('user_service')->autoLogin($request);
      return $this->render('ClassCentralSiteBundle:LandingPages:yearInReview2012.html.twig', array(
          'page' => 'year-in-review-2012',
          'data' => array(
            'year' => '2012',
            'yearsAvailable' => $this->getYearInReviewsAvailable(),
              'leadingArticle' => array(
                  'title' => 'The MOOC Juggernaut: One Year Later',
                  'subtitle' => 'A Review of MOOC Stats and Trends in 2012',
                  'snippet' => 'It’s been a year since the first iteration of free Massively Open Online Courses (MOOCs) from Stanford Faculty went live. On 10th October, 2011 three courses were offered for the first time. These courses were extremely popular with each one attracting over and around 100,000+ students and they spawned two start ups – Coursera and Udacity. Since then a number of universities/initiatives have joined in and the total number of courses announced has exploded to around 250.',
                  'author' => 'Dhawal Shah',
                  'url' => '/report/growth-of-moocs/',
                  'publishedDate' => 'October 15th, 2012',
              ),
          )
        )
      );
    }

    public function yearInReview2013Action(Request $request)
    {
        $this->get('user_service')->autoLogin($request);
        return $this->render('ClassCentralSiteBundle:LandingPages:yearInReview2013.html.twig', array(
                'page' => 'year-in-review-2013',
                'data' => array(
                    'year' => '2013',
                    'yearsAvailable' => $this->getYearInReviewsAvailable(),
                    'leadingArticle' => array(
                        'title' => 'The MOOC Juggernaut: Year 2',
                        'subtitle' => 'A Review of MOOC Stats and Trends in 2013',
                        'snippet' => 'For the first time in history courses that were limited to a few number of people are now open to the entire world. This trend was kickstarted on October 10th, 2011 when three free online courses from Stanford University professors went live. In two years we have gone from no courses to over 900 total announced courses from more than 150 universities. Startups from around the world are gearing up to offer MOOCS and have attracted over $150 million in funding.',
                        'author' => 'Dhawal Shah',
                        'url' => '/report/the-mooc-juggernaut-year-2/',
                        'publishedDate' => 'October 9th, 2013',
                    ),
                )
            )
        );
    }

    public function yearInReview2014Action(Request $request)
    {
      $this->get('user_service')->autoLogin($request);
      return $this->render('ClassCentralSiteBundle:LandingPages:yearInReview2014.html.twig', array(
          'page' => 'year-in-review-2014',
          'data' => array(
            'year' => '2014',
            'yearsAvailable' => $this->getYearInReviewsAvailable(),
            'leadingArticle' => array(
              'title' => 'MOOCs in 2014: Breaking Down the Numbers',
              'subtitle' => 'A Review of MOOC Stats and Trends in 2014',
              'snippet' => 'This year, the number of universities offering MOOCs has doubled to cross 400 universities, with a doubling of the number of cumulative courses offered, to 2400. 22 of the top 25 US universities in US News World Report rankings are now offering courses online for free.',
              'author' => 'Dhawal Shah',
              'url' => 'https://www.edsurge.com/news/2014-12-26-moocs-in-2014-breaking-down-the-numbers',
              'publishedDate' => 'December 26th, 2014',
          ),
          )
        )
      );
    }

    public function yearInReview2015Action(Request $request)
    {
      $this->get('user_service')->autoLogin($request);
      return $this->render('ClassCentralSiteBundle:LandingPages:yearInReview2015.html.twig', array(
          'page' => 'year-in-review-2015',
          'data' => array(
            'year' => '2015',
            'yearsAvailable' => $this->getYearInReviewsAvailable(),
            'bannerImage' => 'banner-2015-year-in-review',
            'statsImage' => 'numbers-2015',
            'leadingArticle' => array(
                'title' => 'Less Experimentation, More Iteration',
                'subtitle' => 'A Review of MOOC Stats and Trends in 2015',
                'snippet' => 'Have massive open online courses emerged from the Trough of Disillusionment to the Slopes of Enlightenment? Wherever MOOCs belong on the Gartner Hype Cycle, one thing is clear: there are more courses and students now than ever before...',
                'author' => 'Dhawal Shah',
                'url' => '/report/moocs-stats-and-trends-2015/',
                'publishedDate' => 'December 30th, 2015',
            ),
            'supplementaryReading' => array(
                array(
                    'publishedDate' => 'December 21st, 2015',
                    'title' => 'By The Numbers: MOOCs in 2015',
                    'description' => 'We examine how the MOOC space has grown throughout 2015 in comparison to the same data from 2014. We look at a variety of stats, from numbers of students, to numbers of courses, to the rise in popularity of non-English language courses',
                    'url' => '/report/moocs-2015-stats/'
                ),
                array(
                    'publishedDate' => 'December 27th, 2015',
                    'title' => '5 Biggest MOOC Trends of 2015',
                    'description' => 'Continued Growth in MOOCs fueled by Expanding Availability, Monetization and Funding',
                    'url' => '/report/5-mooc-trends-of-2015/'
                ),
                array(
                    'publishedDate' => 'December 23rd, 2015',
                    'title' => 'MOOC Trends in 2015: Big MOOC Providers Find their Business Models',
                    'description' => 'A comprehensive look at what business models the top MOOC providers have chosen, why they’ve done so, and what it might mean for learners.',
                    'url' => '/report/mooc-business-model/'
                ),
                array(
                    'publishedDate' => 'December 15th, 2015',
                    'title' => 'MOOC Trends in 2015: MOOC Providers Target High School Demographic',
                    'description' => 'MOOC providers are targeting the high school demographic, to the extent that they’re developing programs just for high schoolers. We take a look at what’s available for high schoolers, and from which providers.',
                    'url' => '/report/high-school-courses/'
                ),
                array(
                    'publishedDate' => 'December 14th, 2015',
                    'title' => 'MOOC Trends in 2015: The Death of Free Certificates',
                    'description' => 'Free MOOC certificates are a thing of the past: they have been completely replaced by paid-for certificates. We take a look at how much certificates cost from each major MOOC provider.',
                    'url' => '/report/death-of-free-certificates/'
                ),
                array(
                    'publishedDate' => 'December 11th, 2015',
                    'title' => 'Eyeing Revenue Sustainability: The Two Biggest MOOC Providers Adapt How Their Courses Work',
                    'description' => 'We take a look at the latest announcements from Coursera and edX regarding their courses and pay structures. We also look at the numbers relating to MOOC providers’ revenue.',
                    'url' => '/report/coursera-paywall-edx-discontinues-free-certificates/'
                ),
                array(
                    'publishedDate' => 'December 9th, 2015',
                    'title' => 'MOOC Trends in 2015: Rise of Self Paced Courses',
                    'description' => 'Find out how the format of MOOCs has changed and grown over the past year, what “self paced” means for MOOCs, and how it compares to the “session based” format.',
                    'url' => '/report/mooc-trends-2015-rise-self-paced-courses/'
                ),
            ),
            'classCentral' => array(
                array(
                    'title' => 'Class Central\'s Zeitgeist 2015',
                    'image' => 'cc-2015-zeitgeist.png',
                    'cta' => 'Read Article',
                    'url' => '/report/zeitgeist-moocs-2015/',
                ),
                array(
                    'title' => 'Class Central\'s Best Online Courses 2015',
                    'image' => 'cc-2015-best-online-courses.png',
                    'cta' => 'View Courses',
                    'url' => '/report/best-free-online-courses-2015/',
                ),
                array(
                    'title' => 'Class Central\'s Top Posts of 2015',
                    'image' => 'cc-2015-top-posts.jpg',
                    'cta' => 'Read Article',
                    'url' => '/report/top-posts-2015/',
                ),
            )
          )
      ));
    }

    public function yearInReview2016Action(Request $request)
    {
      $this->get('user_service')->autoLogin($request);
      return $this->render('ClassCentralSiteBundle:LandingPages:yearInReview2016.html.twig', array(
          'page' => 'year-in-review-2016',
          'data' => array(
            'year' => '2016',
            'yearsAvailable' => $this->getYearInReviewsAvailable(),
            'bannerImage' => 'banner-2016-year-in-review',
            'statsImage' => 'numbers-2016',
            'leadingArticle' => array(
                'title' => 'Monetization over Massiveness',
                'subtitle' => 'A Review of MOOC Stats and Trends in 2016',
                'snippet' => 'The modern massive open online course movement, which began when the first “MOOCs” were offered by Stanford professors in late 2011, is now half a decade old. In that time, MOOC providers have raised over $400 million and now employ more than a thousand staff...',
                'author' => 'Dhawal Shah',
                'url' => '/report/moocs-stats-and-trends-2016/',
                'publishedDate' => 'December 29th, 2016',
            ),
            'supplementaryReading' => array(
                array(
                    'publishedDate' => 'December 25th, 2016',
                    'title' => 'By The Numbers: MOOCs in 2016',
                    'description' => 'How has the MOOC space grown this year? Get the facts, figures, and pie charts',
                    'url' => '/report/mooc-stats-2016/'
                ),
                array(
                    'publishedDate' => 'December 22nd, 2016',
                    'title' => '6 Biggest MOOC Trends of 2016',
                    'description' => 'MOOCs: Yes to Monetization, No to being Massive — and more',
                    'url' => '/report/biggest-mooc-trends-2016/'
                ),
                array(
                    'publishedDate' => 'December 20th, 2016',
                    'title' => 'MOOC Trends in 2016: College Credit, Credentials, and Degrees',
                    'description' => 'You can now earn college credits from MOOCs. But are there any takers?',
                    'url' => '/report/mooc-trends-credit-credentials-degrees/'
                ),
                array(
                    'publishedDate' => 'November 16th, 2016',
                    'title' => 'MOOC Trends in 2016: MOOCs No Longer Massive',
                    'description' => 'We’ve gained the ability to take MOOCs at any time, but lost something in the process',
                    'url' => '/report/moocs-no-longer-massive/'
                ),
            ),
            'providers' => array(
                array(
                    'name' => 'Coursera',
                    'iconImage' => 'icon-provider-coursera',
                    'description' => 'A look at Coursera’s evolution in 2016',
                    'url' => '/report/coursera-2016-review/',
                ),
                array(
                    'name' => 'edX',
                    'iconImage' => 'icon-provider-edx',
                    'description' => 'Ten million registered users, ~1300 courses, and 109 partners',
                    'url' => '/report/edx-2016-review/',
                ),
                array(
                    'name' => 'Udacity',
                    'iconImage' => 'icon-provider-udacity',
                    'description' => '13,000 currently students enrolled in its Nanodegrees, 3000 graduates, and 900 have gotten jobs.',
                    'url' => '/report/udacity-2016-review/',
                ),
                array(
                    'name' => 'FutureLearn',
                    'iconImage' => 'icon-provider-futurelearn',
                    'description' => 'Futurelearn crosses 5 million learners, launches new credential, and announces six postgraduate degrees.',
                    'url' => '/report/futurelearn-2016-review/',
                ),
            ),
            'classCentral' => array(
                array(
                    'title' => 'Class Central\'s Zeitgeist 2016',
                    'image' => 'cc-2016-zeitgeist.png',
                    'cta' => 'Read Article',
                    'url' => '/report/zeitgeist-moocs-2016/',
                ),
                array(
                    'title' => 'Class Central\'s Best Online Courses 2016',
                    'image' => 'cc-2016-best-online-courses.png',
                    'cta' => 'View Courses',
                    'url' => '/report/best-free-online-courses-2016/',
                ),
                array(
                    'title' => 'Class Centrals 2016: Year in Review',
                    'image' => 'cc-2016-year-in-review.png',
                    'cta' => 'Read Article',
                    'url' => '/report/class-central-2016-review/',
                ),
            )
          )
      ));
    }

    private function getSubjectList()
    {
        return [
            [
                'name' => 'Computer Science',
                'slug' => 'cs',
                'numCourses' => '218'
            ],
            [
                'name' => 'Business',
                'slug' => 'business',
                'numCourses' => '315'
            ],
            [
                'name' => 'Humanities',
                'slug' => 'humanities',
                'numCourses' => '167'
            ],
            [
                'name' => 'Data Science',
                'slug' => 'data-science',
                'numCourses' => '77'
            ],
            [
                'name' => 'Personal Development',
                'slug' => 'personal-development',
                'numCourses' => '61'
            ],
            [
                'name' => 'Programming',
                'slug' => 'programming-and-software-development',
                'numCourses' => '161'
            ],
            [
                'name' => 'Art and Design',
                'slug' => 'art-and-design',
                'numCourses' => '104'
            ],
            [
                'name' => 'Health',
                'slug' => 'health',
                'numCourses' => '123'
            ],
            [
                'name' => 'Mathematics',
                'slug' => 'maths',
                'numCourses' => '40'
            ],
            [
                'name' => 'Engineering',
                'slug' => 'engineering',
                'numCourses' => '146'
            ],
            [
                'name' => 'Science',
                'slug' => 'science',
                'numCourses' => '167'
            ],
            [
                'name' => 'Education & Teaching',
                'slug' => 'education',
                'numCourses' => '144'
            ],
            [
                'name' => 'Social Sciences',
                'slug' => 'social-sciences',
                'numCourses' => '224'
            ],
        ];
    }

    private function getYearInReviewsAvailable() {
        return array(2016,2015,2014, 2013, 2012);
    }
}
