{{$user_name}} is logged into Lotto 4D successfully....
@php
$url   = env('APP_ENV') === 'local' ? 'http://127.0.0.1:3000/api/login' : 'http://kk-lotto.com/api/login';
@endphp

<form class="col-md-6" method="post" action="{{ $url }}" id="form_id" >
   @csrf
         <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="enterprise_id" required    id="enterprise_id" value="{{$id}}"/>
       <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="customer_name"  value="{{$user_name}}"   id="customer_name"  />
       <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="user_name"  value="{{$user_name}}"   id="user_name"  />
       <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="customer_id"  value="{{$customer_id}}"   id="customer_id"  />
       <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="language"  value="{{$language}}"   id="language"  />
       <input maxlength="50" minlength="1" autofocus type="hidden" class="form-control" name="token"  value="{{$token}}"   id="token"  />
       <input type="submit" class="btn btn-primary" name="submit" value="{{ __('messages.Submit') }}"  id="submitBtnId" style="display: none;"/>
 </form>




 <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

<script>    $('#submitBtnId').click();</script>
