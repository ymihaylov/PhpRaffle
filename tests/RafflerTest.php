<?php
require_once "ServiceTestCase.php";

class CloudLxcTest extends ServiceTestCase
{
	private static $_expectedPrices = array(
		'cloud_lxc' 	=> 55.66,
	);

	private static $_expectedModPrices = array(
		'mem' 	=> 2.22,
		'cpu' 	=> 3.33,
		'hdd' 	=> 4.44,
	);

	private function _getMockAccount( $resourceParameters = array(), $additionalOverrideMethods = array() )
	{
// 		$overrideMethodsArr = array( 'getClient', 'getProduct', 'getCustomRenewalRecord' );
		$overrideMethodsArr = array( 'getClient', 'getProduct', 'getModifiers' );
		if ( !empty( $additionalOverrideMethods ) )
			$overrideMethodsArr = array_merge( $overrideMethodsArr, $additionalOverrideMethods );

		$account = $this->getMockBuilder( 'Hosting_Account' )
			->setMethods( $overrideMethodsArr ) // Replace just those methods, leave the others as they are
			->getMock();

		$account->id 			= 0;
		$account->domain 		= 'irintchev.com';
		$account->product_id 	= 590; // Cloud_lxc, 1m, Chicago
		$accountType			= 'cloud_lxc';
		$billingCycle 			= 1;

		$account->expects( $this->any() )
			->method( 'getClient' )
			->will( $this->returnValue( $this->_getMockClient() ) );

		$account->expects( $this->any() )
			->method( 'getProduct' )
			->will( $this->returnValue(
				$this->_getMockProduct( $account->product_id, 'hosting', $accountType,
					self::$_expectedPrices[ $accountType ], $billingCycle )
		));

		if ( is_array( $resourceParameters ) )
		{
			$modGroup = 'Parameter';
			$mods = array();
			foreach ( $resourceParameters as $rType => $value )
				$mods[] = $this->_getMockAccountModifier( $rType, $value, $modGroup, $accountType );

			$account->expects( $this->any() )
				->method( 'getModifiers' )
				->will( $this->returnValue( $mods ) );
		}

		return $account;
	}

	private function _getMockAccountModifier( $modName, $number = 1, $modGroup = 'Parameter', $modType = 'cloud_lxc' )
	{
		$modifier = $this->getMockBuilder( 'Hosting_Account_Modifier' )
			->setMethods( array( 'getProductModifier' ) )
			->getMock();

		$modifier->number = $number;
		$modifier->expects( $this->any() )
			->method( 'getProductModifier' )
			->will( $this->returnValue( $this->_getMockProductModifier( $modName, $modGroup, $modType ) ) );

		return $modifier;
	}

	private function _getMockProductModifier( $modName, $modGroup = 'Parameter', $modType = 'cloud_lxc' )
	{
		$modifier = $this->getMockBuilder( 'HostingCore_Product_Modifier' )
			->setMethods( array( 'getRenewalPrice' ) )
			->getMock();

		$modifier->type 		= $modType;
		$modifier->mod_group 	= $modGroup;
		$modifier->name 		= $modName;
		$modifier->expects( $this->any() )
			->method( 'getRenewalPrice' )
			->will( $this->returnValue( self::$_expectedModPrices[ $modName ] ) );

		return $modifier;
	}

	public function test_getAccountParams_noAutoScales()
	{
		$additionalMethods = array( 'getModifiersByTypeGroup' );
		$resources = array( 'mem' => 8, 'cpu' => 4, 'hdd' => 40 );
		$mockAccount = $this->_getMockAccount( $resources, $additionalMethods );

		$this->_setGetModifiersByTypeGroupResourcesForAccount( $mockAccount, $resources );

		$params = Service_Hosting_CloudLxc::getAccountParams( $mockAccount, false );
		$this->assertEquals( $resources, $params );
	}

	public function ttest_getAccountParams_withAutoScales()
	{
		$additionalMethods = array( 'getModifiersByTypeGroup', 'getUpgrades' );
		$resources = array( 'mem' => 8, 'cpu' => 4, 'hdd' => 40 );
		$mockAccount = $this->_getMockAccount( $resources, $additionalMethods );

		$this->_setGetModifiersByTypeGroupResourcesForAccount( $mockAccount, $resources );

		$mockMemUpgs = array();
		$mockMemUpgs[] = $this->_getMockAutoscaleUpgrade( 'mem', 4 );
		$upgrade = $this->_getMockAutoscaleUpgrade( 'mem', 8 );
		$upgrade->cancelled = 1; // It would still count
		$mockMemUpgs[] = $upgrade;
		$upgrade = $this->_getMockAutoscaleUpgrade( 'mem', 2 );
		$upgrade->cancelled = 1;
		$upgrade->terminated = 1; // This one would not count, though
		$mockMemUpgs[] = $upgrade;

		$mockCpuUpgs = array();
		$mockCpuUpgs[] = $this->_getMockAutoscaleUpgrade( 'cpu', 1 );
		$mockCpuUpgs[] = $this->_getMockAutoscaleUpgrade( 'cpu', 2 );

		$map = array(
			array( 'autoscale_mem', null, null, false, false, $mockMemUpgs ),
			array( 'autoscale_cpu', null, null, false, false, $mockCpuUpgs ),
			array( 'autoscale_hdd', null, null, false, false, array() ),
		);

		$mockAccount->method( 'getUpgrades' )
			->will( $this->returnValueMap( $map ) );

		$expectedParams = $resources;
		$mockAutoscaleUpgrades = array_merge( $mockMemUpgs, $mockCpuUpgs );

		foreach ( $mockAutoscaleUpgrades as $mockUpgrade )
		{
			$rType = str_replace( 'autoscale_', '', $mockUpgrade->type );
			if ( !$mockUpgrade->terminated )
				$expectedParams[ $rType ] += $mockUpgrade->additional;
		}

		$params = Service_Hosting_CloudLxc::getAccountParams( $mockAccount, true );
		$this->assertEquals( $expectedParams, $params );
	}

	private function _setGetModifiersByTypeGroupResourcesForAccount( $mockAccount, $resources )
	{
		$modGroup = 'Parameter';
		$mods = array();
		foreach ( $resources as $rType => $value )
			$mods[] = $this->_getMockAccountModifier( $rType, $value, $modGroup );

		$mockAccount->expects( $this->any() )
			->method( 'getModifiersByTypeGroup' )
			->will( $this->returnValue( $mods ) );
	}

	private function _getMockAutoscaleUpgrade( $rType, $amount )
	{
		$mockUpgrade = $this->getMock( 'Hosting_Upgrade' );
		$mockUpgrade->type 			= 'autoscale_' . $rType;
		$mockUpgrade->additional 	= $amount;

		return $mockUpgrade;
	}

	public function ttest_processAutoscaleOrder_Tax()
	{
		// Now test with a mock Spanish client
		$mockClient = $this->getMockBuilder( 'HostingCore_Client' )
			->setMethods( array( 'getServiceCompany' ) )
			->getMock();

		$mockClient->service_company_id = 3;
		$mockClient->lang 				= 'es_ES';

		$mockSC = $this->getMockBuilder( 'HostingCore_ServiceCompany' )
			->setMethods( array( 'getTaxEngine' ) )
			->getMock();

		$mockSC->id = 3;

		$mockTaxEngine = $this->getMockBuilder( 'TaxEngine_EuVat' )
			->setMethods( array( 'getTaxDataFromClientDynamic', 'getTaxRate' ) )
			->getMock();

	    $mockDataFromClient = [
			'country_code' 	=> 'BG',
			'vat_number' 	=> 'BG0000000',
			'company' 		=> 'Az Brato i Averite',
			'client_type' 	=> 'business',
			'zip' 			=> 1166,
			'state' 		=> 'XX',
	    ];
		$mockTaxEngine->expects( $this->any() )
			->method( 'getTaxDataFromClientDynamic' )
			->will( $this->returnValue( $mockDataFromClient ) );

		$expectedTaxRate = 20.00;
		$mockTaxEngine->expects( $this->any() )
			->method( 'getTaxRate' )
			->will( $this->returnValue( $expectedTaxRate ) );

		$mockSC->expects( $this->any() )
			->method( 'getTaxEngine' )
			->will( $this->returnValue( $mockTaxEngine ) );

		$mockClient->expects( $this->any() )
			->method( 'getServiceCompany' )
			->will( $this->returnValue( $mockSC ) );


		$mockOrder = $this->getMockBuilder( 'HostingCore_Order' )
			->setMethods( array( 'saveFull', 'getClient' ) )
			->getMock();

		$mockOrder->expects($this->exactly(1))
            ->method('saveFull')
            ->with( true, $expectedTaxRate )
			->will( $this->returnValue( false ) );

		$mockOrder->expects($this->any())
            ->method('getClient')
			->will( $this->returnValue( $mockClient ) );


		// $mockServiceCloudLxc = new Service_Hosting_CloudLxc;
		// $method = new ReflectionMethod( $mockServiceCloudLxc, '_processAutoscaleOrder');
		// $method->setAccessible( true );

		// $mockServiceCloudLxc = $this->getMockBuilder( 'Service_Hosting_CloudLxc' )
		//	->disableOriginalConstructor()
		// 	->getMock();

	    // $reflection = new ReflectionClass( $mockServiceCloudLxc );
	    // $method = $reflection->getMethod('_processAutoscaleOrder');
	    // $method->setAccessible( true );

		$mockServiceCloudLxc = new Service_Hosting_CloudLxc;
		$result = $mockServiceCloudLxc->_processAutoscaleOrder( $mockOrder, $mockClient );

		Utils_Dump::dump( $result );
	}
}
