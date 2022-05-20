<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Process</title>
</head>
<body onload="document.forms[0].submit()">
<h2>Please Wait...
    <form method="post" type="redirect" action="{{env('PAYTM_ACTION')}}processTransaction?mid={{env('PAYTM_MID')}}&orderId={{$order_id}}">
        <input type="text" name="mid"  value="{{env('PAYTM_MID')}}" />
        <input type="text" name="orderId"  value="{{$order_id}}" />
        <input type="text" name="txnToken"  value="{{$txn_token}}" />
        <input type="text" name="paymentMode"  value="UPI" />
        <input type="text" name="payerAccount"  value="{{$method}}" />
  <button type="submit">Submit</button>
     </form>
</body>
</html>   
   
  