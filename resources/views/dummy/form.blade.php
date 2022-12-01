<style>
  .mandatory{color: red;}
</style>

    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            {{-- <h1 class="m-0">@if($data->id=="")
                   {{ __('messages.Add') }}
                    @else
                        {{ __('messages.Edit') }}
                    @endif
                    {{ __('messages.'.$heading) }}</h1> --}}
          </div><!-- /.col -->
          <div class="col-sm-6 text-right">
<!--<a href="{{ url()->previous() }}" class="btn btn-warning" ><i class="fa fa-angle-double-left" ></i> Back</a>          </div>--><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            
        <div class="row">
          <div class="col-lg-12">
            <section class="card">
              
              <div class="card-body">
                <div class="notification"></div>              

                <div class="card card-secondary">
                  <header class="card-header">User</header>
                      <div class="row card-body">
                  <form class="col-md-6" method="post" action="{{url('/')}}/admin/dummy/checkvalidid" id="form_id" enctype="multipart/form-data">
                    @csrf
                    <div class="col-md-12">
                    <div class="row">        
                    <div class="col-sm-12 form-group">
                          <label class=" control-label">Customer Name<span class="mandatory">*</span></label>
                          <input maxlength="50" minlength="1" autofocus type="text" class="form-control" name="user_name" required    id="user_name" value="{{old('user_name') }}"/>  
                        </div>
                        <div class="col-sm-12 form-group">
                          <label class=" control-label">Customer Email<span class="mandatory">*</span></label>
                          <input maxlength="50" minlength="1" autofocus type="text" class="form-control" name="email" required    id="email" value="{{old('email') }}"/>
                        </div>       
                        <div class="col-sm-12 form-group">
                          <label class=" control-label">Enterprise ID<span class="mandatory">*</span></label>
                          <input maxlength="50" minlength="1" autofocus type="text" class="form-control" name="enterprise_id" required    id="enterprise_id" value="{{old('enterprise_id') }}"/>
                        </div>
              
                      <div class="col-sm-12 form-group">
                        <label class=" control-label">Customer ID<span class="mandatory">*</span></label>
                        <input maxlength="50" minlength="1" autofocus type="text" class="form-control" name="customer_id" required   id="customer_id" value="{{old('customer_id') }}"/>
                      </div>
              
        
                      <div class="col-sm-12 form-group">
                        <label class=" control-label">Token </label>
                        <input maxlength="50" minlength="1" autofocus type="text" class="form-control" name="token"     id="token"  />
                      </div>
                    </div> 
                    <div class="row">
                      <div class="col-sm-12 form-group text-right">                  
                        <a href="#" onclick="document.getElementById('form_id').reset(); document.getElementById('form_id').value = null; return false;" class="btn btn-secondary">{{ __('messages.Reset') }}</a>

                        <input type="submit" class="btn btn-primary" name="submit" value="{{ __('messages.Submit') }}"  id="submitBtnId"/>
                      </div>
                    </div>
                    </div>                    
                  </form>
                </div>
                </div>
              </div>

              
            </section>
          </div>


                   

        </div>
    </section>

                           



 
<script>
  var baseUrl = '{{url("/")}}';
    $(function () {
        // Summernote
        
      

        $('#form_dummy').validate({
        rules: {       
            user_name: {
              required: true,
              
          },
         password: {
          required: true,
              
          }          
            
        },
        messages: {
         
          user_name: {
              required: "Please enter username",
              
          },
         password: {
              required: "Please enter password",
              
          }
          
        },
        submitHandler: function(form) {
                $.ajax({
                  url: form.action,
                  type: form.method,
                  data: $(form).serialize(),   
                  dataType:'JSON',           
                success:function(res){
                  if(res.success){
                    $('.notification').html('<p class="alert alert-success">'+res.message+'</p>');   
                   $('#token').val(res.data);
                  }else{
                    $('.notification').html('<p class="alert alert-danger">'+res.message+'</p>'); 
                  }                  
                },
                error:function(err){
                  $('.notification').html('<p class="alert alert-danger">'+res.message+'</p>'); 
                }
      
              });
              return false;

        },
        errorElement: 'span',
        errorPlacement: function (error, element) {
          error.addClass('invalid-feedback');
          element.closest('.form-group').append(error);
        },
        highlight: function (element, errorClass, validClass) {
          $(element).addClass('is-invalid');
        },
        unhighlight: function (element, errorClass, validClass) {
          $(element).removeClass('is-invalid');
        }
  });

 

    });
    
</script>
