<form action="" method="get" class="navbar-form navbar-left" role="search">
    <div class="input-group">
        <span class="input-group-addon bg-transparent"><i class="fa fa-question fa-fw img-circle smaller" aria-hidden="true"></i></span>
        <input type="text" class="form-control" placeholder="Search all Signature content" id="kw" name="kw" size="35"
               maxlength="255" required="required" data-msg-required="" data-rule-minlength="4" data-msg-minlength="" value="{{{ Input::get('kw') }}}"/>
        <span class="input-group-btn">
            <button type="submit" class="btn btn-default"><i class="fa fa-search bigger" aria-hidden="true"></i></button>
        </span>
    </div>
</form>
