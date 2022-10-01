<?php
require_once($_SERVER["DOCUMENT_ROOT"].'settings/crest.php');

//get parametres and add them to variables
$arDocument = $_REQUEST['document_id'];
$paramCity = $_REQUEST['properties']['city'];
$paramDeliveryAddress = $_REQUEST['properties']['delivery_address'];
$paramPaymentMethodName = $_REQUEST['properties']['payment_method_name'];
$paramPaymentMethodId = $_REQUEST['properties']['payment_method_id'];
$paramOrderDate = $_REQUEST['properties']['order_date'];
$paramPaymentStatus = $_REQUEST['properties']['payment_status'];
$paramTtnNumber = $_REQUEST['properties']['ttn_number'];
$paramTtnDate = $_REQUEST['properties']['ttn_date'];

//checking document type (in our case LEAD or DEAL)
if (is_array($arDocument))
{
    foreach ($arDocument as $param)
    {// get document id
        if (strpos($param, "DEAL_") === 0)
        {
            $deal_id = intval(substr($param,  strlen("DEAL_")));
            break;
        }
        else if (strpos($param, 'LEAD_') === 0)
        {
            $lead_id = intVal(substr($param, strlen('LEAD_')));
            break;
        }
    }
}

if ($lead_id) 
{   
    $currentLead = CRest::call('crm.lead.get',['id' => $lead_id]);
    //robot will work only when LEAD STATUS_ID is NEW
    if ($currentLead['result']['STATUS_ID'] == "NEW")
    {
        //get information about contact attached to LEAD
        $currentContact = CRest::call('crm.contact.get',['id' => $currentLead['result']["CONTACT_ID"]]);
        $currentContactEmail = $currentContact['result']["EMAIL"][0]["VALUE"];
        $currentContactPhone = $currentContact['result']["PHONE"][0]["VALUE"];
        $currentContactName = $currentContact['result']['NAME'] . " " . $currentContact['result']['LAST_NAME'];
        $currentCity = $currentLead['result'][$paramCity];
        $currentDeliveryAdres = $currentLead['result'][$paramDeliveryAddress];

        //get payment methods in LEAD user forms     
        $currentPaymentMethodId = $currentLead['result'][$paramPaymentMethodName];
            $currentPaymentMethodFields = CRest::call('crm.lead.userfield.get',['ID' => $paramPaymentMethodId]);
            foreach ($currentPaymentMethodFields['result']['LIST'] as $allPayMethods) {
                if ($allPayMethods['ID'] == $currentPaymentMethodId) 
                {
                    $currentPaymentMethod = $allPayMethods['VALUE'];
                }
            }
        //get order date
        $currentOrderDate = date('Y-m-d',strtotime($currentLead['result'][$paramOrderDate]));

        // get ordered products 
         $allProductsInLead = CRest::call('crm.lead.productrows.get', ['ID' => $lead_id]);
        
         foreach($allProductsInLead['result'] as $product)
         {   
             //create new array of products to add into JSON array     
             $productsArray[] = array(
             "product_name" => $product['PRODUCT_NAME'],
             "prodcut_amount" => $product['QUANTITY'],
             "product_price" =>  $product['PRICE']
             );
         }
        //get total products sum 
        $currentSum = $currentLead['result']["OPPORTUNITY"];

        //get payment status
        $currentPaymentStatus = $currentLead['result'][$paramPaymentStatus]; 
        
        $document_id = $lead_id;
    }
} 
else if ($deal_id) 
{
    //get current Deal
    $currentDeal = CRest::call('crm.deal.get',['id' => $deal_id]);
    
    //get stage of deal
    $stageId = $currentDeal['result']['STAGE_ID'];
    
    //get current Contact
    $currentContact = CRest::call('crm.contact.get',['id' => $currentDeal['result']["CONTACT_ID"]]);
  
    //get current contact email 
    $currentContactEmail = $currentContact['result']["EMAIL"][0]["VALUE"];
    
    //get current Phone number
    $currentContactPhone = $currentContact['result']["PHONE"][0]["VALUE"];
    
    //get current Contact name
    $currentContactName = $currentContact['result']['NAME'] . " " . $currentContact['result']['LAST_NAME'];
    
    //get current city
    $currentCity = $currentDeal['result'][$paramCity];

    //get current delivery adres
    $currentDeliveryAdres = $currentDeal['result'][$paramDeliveryAddress];
   
    //get current payment method - need to add to params ID field and field name
    $currentPaymentMethodId = $currentDeal['result'][$paramPaymentMethodName];
    $currentPaymentMethodFields = CRest::call('crm.deal.userfield.get',['ID' => $paramPaymentMethodId]);
    foreach ($currentPaymentMethodFields['result']['LIST'] as $allPayMethods) {
        if ($allPayMethods['ID'] == $currentPaymentMethodId) 
        {
            $currentPaymentMethod = $allPayMethods['VALUE'];
        }
    }

    //get order date -  need to add to params
    $currentOrderDate = date('Y-m-d',strtotime($currentDeal['result'][$paramOrderDate]));
 
    //get ordered products - no need to add to params
    $allProductsInDeal = CRest::call('crm.deal.productrows.get', ['ID' => $deal_id]);
     
    foreach($allProductsInDeal['result'] as $product)
    {     
        //create new array of products to add into JSON array    
        $productsArray[] = array(
        "product_name" => $product['PRODUCT_NAME'],
        "product_amount" => $product['QUANTITY'],
        "product_price" =>  $product['PRICE']
        );
    }

    //get payment status 
    $currentPaymentStatus = $currentDeal['result'][$paramPaymentStatus];

    //get total sum
    $currentSum = $currentDeal['result']["OPPORTUNITY"];

    //get ttn number
    $currentTtnNumber = $currentDeal['result'][$paramTtnNumber];

    //get ttn date
    $currentTtnDate= $currentDeal['result'][$paramTtnDate];

    $document_id = $deal_id;
}

 // Setup request to send json via POST raw
$phpToJson = array(
"email" => $currentContactEmail,
"phone" => $currentContactPhone,
"name" => $currentContactName,
"city" => $currentCity,
"delivery_address" => $currentDeliveryAdres,
"payment_method" => $currentPaymentMethod,
"order_date" => $currentOrderDate,
"order_id" => $document_id,
"products" => $productsArray,
"order_sum" => $currentSum,
"payment_status" => $currentPaymentStatus,
"ttn_number" => $currentTtnNumber,
"ttn_date" => $currentTtnDate,
);

if ($lead_id) {
    $urlSendPulse = 'send_pulse_event_id';
    sendJsonToSendPulse($urlSendPulse, $phpToJson);  
}
else if ($deal_id)
{
    switch ($stageId) 
    { 
        case '1': // Stage_1
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;
        case '2': // Stage_2
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;
        case '3': // Stage_3
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;
        case '4': // Stage_4
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;
        case '5': // Stage_5
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;
        case '6': // Stage_6
            $urlSendPulse = 'send_pulse_event_id';
            sendJsonToSendPulse($urlSendPulse, $phpToJson);
            break;    
    }
}

function sendJsonToSendPulse($urlSendPulse, $phpToJson) 
{ 
    $curlInit = curl_init($urlSendPulse);
    $sendPulseArray = json_encode($phpToJson);
    curl_setopt($curlInit, CURLOPT_POSTFIELDS, $sendPulseArray);
    curl_setopt($curlInit, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
    curl_setopt($curlInit, CURLOPT_POST, true);
    curl_setopt($curlInit, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curlInit, CURLOPT_SSL_VERIFYPEER, 0);    
    curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($curlInit);
    curl_close($curlInit);
}

?>



