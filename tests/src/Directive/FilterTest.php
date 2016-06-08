<?php
/**
 * Copyright 2015-2016 Xenofon Spafaridis
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace Phramework\JSONAPI\Directive;

use Phramework\Exceptions\Exception;
use Phramework\JSONAPI\InternalModel;
use Phramework\JSONAPI\Relationship;
use Phramework\JSONAPI\ValidationModel;
use Phramework\Operator\Operator;
use Phramework\Validate\BooleanValidator;
use Phramework\Validate\ObjectValidator;
use Phramework\Validate\StringValidator;
use Phramework\Validate\UnsignedIntegerValidator;
use Zend\Diactoros\ServerRequest;

/**
 * @coversDefaultClass Phramework\JSONAPI\Directive\Filter
 * @license https://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 * @author Xenofon Spafaridis <nohponex@gmail.com>
 */
class FilterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var InternalModel
     */
    protected $articleModel;

    /**
     * @var ServerRequest
     */
    protected $request;

    public function setUp()
    {
        $this->articleModel = (new InternalModel('article'))
            ->setValidationModel(new ValidationModel(
                new ObjectValidator()
            ))->setFilterValidator(new ObjectValidator((object) [
                'id'      => new UnsignedIntegerValidator(0, 10),
                'meta' => new ObjectValidator((object) [
                    'timestamp' => new UnsignedIntegerValidator(),
                    'keywords'  => new StringValidator()
                ]),
                'updated' => new UnsignedIntegerValidator(),
                //'tag'     => new StringValidator()
            ]))->setValidationModel(new ValidationModel(new ObjectValidator(
                (object) [
                    'title'  => new StringValidator(2, 32),
                    'status' => (new BooleanValidator())
                        ->setDefault(true)
                ],
                ['title']
            )))->setFilterableAttributes((object) [
                'no-validator' => Operator::CLASS_COMPARABLE,
                'status'       => Operator::CLASS_COMPARABLE,
                'meta'         => Operator::CLASS_JSONOBJECT
                    | Operator::CLASS_NULLABLE
                    | Operator::CLASS_COMPARABLE,
                //'tag'          => Operator::CLASS_IN_ARRAY,
                'updated'      => Operator::CLASS_ORDERABLE
                    | Operator::CLASS_NULLABLE,
            ])->setRelationships((object) [
                'creator' => new Relationship(
                    (new InternalModel('user'))
                    ->setValidationModel(new ValidationModel(
                        new ObjectValidator(
                        )
                    )),
                    Relationship::TYPE_TO_ONE,
                    'creator-user_id'
                ),
                'tag' => new Relationship(
                    (new InternalModel('tag'))
                    ->setValidationModel(new ValidationModel(
                        new ObjectValidator(
                        )
                    )),
                    Relationship::TYPE_TO_MANY,
                    null,
                    (object) [
                        'GET'   => function () {},
                        'POST'  => function () {},
                        'PATCH' => function () {}
                    ]
                )
            ]);

        $this->request = new ServerRequest(
            [],
            [],
            null,
            null,
            'php://input',
            [],
            [],
            []
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct1()
    {
        $filter = new Filter(
            [1, 2, 3],
            (object) [
                'tag' => [1, 2]
            ]
        );
    }

    /**
     * @covers ::__construct
     */
    public function testConstruct2()
    {
        $filter = new Filter(
            [1, 2, 3],
            null
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure2()
    {
        $filter = new Filter(
            [],
            (object) [
                'tag' => new \stdClass() //not an array
            ]
        );
    }

    /**
     * @covers ::__construct
     * @expectedException Exception
     */
    public function testConstructFailure3()
    {
        $filter = new Filter(
            [],
            null,
            [
                new \stdClass()
            ]
        );
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestPrimaryUsingIntval()
    {

        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    $this->articleModel->getResourceType() => 4
                ]
            ]),
            $this->articleModel
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertEquals([4], array_values($filter->getPrimary()));
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestRelationshipEmpty()
    {
        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'tag' => Operator::OPERATOR_EMPTY
                ]
            ]),
            $this->articleModel
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertEquals(
            Operator::OPERATOR_EMPTY,
            $filter->getRelationships()->tag
        );
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequestEmpty()
    {
        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([]),
            $this->articleModel
        );

        $this->assertNull($filter);
    }

    /**
     * @covers ::parseFromRequest
     */
    public function testParseFromRequest()
    {
        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'article'   => '1, 2',
                    'tag'       => '4, 5, 7',
                    'creator'   => '1',
                    'status'    => [true, false],
                    'title'     => [
                        Operator::OPERATOR_LIKE . 'blog',
                        Operator::OPERATOR_NOT_LIKE . 'welcome'
                    ],
                    'updated'   => Operator::OPERATOR_NOT_ISNULL,
                    'meta.keywords' => 'blog'
                ]
            ]),
            $this->articleModel
        );

        $this->assertInstanceOf(Filter::class, $filter);

        $this->assertContainsOnly(
            'string',
            $filter->getPrimary(),
            true
        );

        $this->assertInternalType(
            'object',
            $filter->getRelationships()
        );

        $this->assertInternalType(
            'array',
            $filter->getRelationships()->tag
        );

        $this->assertContainsOnly(
            'string',
            $filter->getRelationships()->tag,
            true
        );

        $this->assertContainsOnlyInstancesOf(
            FilterAttribute::class,
            $filter->getAttributes()
        );

        $this->assertSame(
            ['1', '2'],
            $filter->getPrimary()
        );

        $this->assertSame(
            ['4', '5', '7'],
            $filter->getRelationships()->tag
        );
        $this->assertSame(
            ['1'],
            $filter->getRelationships()->creator
        );

        $this->assertCount(6, $filter->getAttributes());

        $shouldContain1 = new FilterAttribute(
            'title',
            Operator::OPERATOR_LIKE,
            'blog'
        );

        $shouldContain2 = new FilterJSONAttribute(
            'meta',
            'keywords',
            Operator::OPERATOR_EQUAL,
            'blog'
        );

        $found1 = false;
        $found2 = false;

        foreach ($filter->getAttributes() as $filterAttribute) {
            if ($shouldContain1 == $filterAttribute) {
                $found1 = true;
            } elseif ($shouldContain2 == $filterAttribute) {
                $found2 = true;
            }
        }

        $this->assertTrue($found1);
        $this->assertTrue($found2);
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailurePrimaryNotString()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'article'   => [1, 2]
                ]
            ]),
            $this->articleModel
        );
    }


    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailurePrimaryToParse()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    $this->articleModel->getResourceType() => 10000
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureRelationshipNotString()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'tag'   => [1, 2]
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureNotAllowedAttribute()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'not-found'   => 1
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     * @expectedException \Exception
     */
    public function testParseFromRequestFailureAttributeWithoutValidator()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'no-validator'   => 1
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureAttributeIsArray()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'updated'   => [[Operator::OPERATOR_ISNULL]]
                ]
            ]),
            $this->articleModel
        );
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureAttributeToParse()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'updated'   => 'qwerty'
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureAttributeNotAllowedOperator()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'status'   => Operator::OPERATOR_GREATER_EQUAL . '1'
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureAttributeNotAcceptingJSONOperator()
    {
        Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'status.ok'   => true
                ]
            ]),
            $this->articleModel
        )->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\IncorrectParameterException
     */
    public function testParseFromRequestFailureAttributeUsingJSONPropertyValidator()
    {
        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'meta.timestamp'   => 'xsadas'
                ]
            ]),
            $this->articleModel
        );

        $filter->validate($this->articleModel);
    }

    /**
     * @covers ::parseFromRequest
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testParseFromRequestFailureAttributeJSONSecondLevel()
    {
        $filter = Filter::parseFromRequest(
            $this->request->withQueryParams([
                'filter' => [
                    'meta.timestamp.time'   => 123456789
                ]
            ]),
            $this->articleModel
        );

        $filter->validate($this->articleModel);
    }

    /**
     * @covers ::validate
     */
    public function testValidate()
    {
        $filter = new Filter(
            [],
            (object) [
                'tag' => [1, 2, 3]
            ]
        );

        $model = $this->articleModel;

        $filter->validate($model);
    }

    /**
     * @covers ::validate
     * @expectedException \Phramework\Exceptions\RequestException
     */
    public function testValidateFailureRelationshipNotFound()
    {
        $filter = new Filter(
            [],
            (object) [
                'not-found-relationship' => [1, 2, 3]
            ]
        );

        $filter->validate($this->articleModel);
    }
}
