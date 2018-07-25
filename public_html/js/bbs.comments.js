var jComments = (function(){
    var inited = false, o = {lang:{}, item_id:0};
    var $block;

    function init()
    {
        $block = $('#j-comments-block');

        o.item_id = intval(o.item_id);

        $block.on('click', '.j-comment-add', function(e){
            e.preventDefault();
            var $el = $(this);
            var $comment = $el.closest('.j-comment');
            var $form = $comment.find('.j-comment-add-form');
            $el.closest('.j-comment-actions').addClass('hide');
            $form.parent().removeClass('hide');
            $el.closest('.j-comment-block').find('.j-comments-more').trigger('click');

            if (!$form.hasClass('j-inited')) {
                $form.addClass('j-inited');
                app.form($form, function(){
                    var f = this;
                    if( ! f.checkRequired({focus:true}) ) return;
                    if (!bff.filter('bbs.comments.beforeSubmit', true, f, o)) {
                        return false;
                    }

                    f.ajax(bff.ajaxURL('bbs&ev=comments', 'add'), {}, function(resp, errors){
                        if(resp && resp.success) {
                            f.$field('message').val('');
                            if (resp.premod) {
                                f.alertSuccess(o.lang.premod_message);
                                $comment.find('.j-comment-cancel').trigger('click');
                                return;
                            }
                            if (resp.html) {
                                var $par = $el.closest('.j-comment-block');
                                var scroll = false;
                                if (!$par.length) {
                                    $par = $block.find('.j-comment-block:first');
                                    scroll = true;
                                }
                                $par.append(resp.html);
                                if(scroll){
                                    $.scrollTo($par.find('.j-comment-block:last'), {duration:300, offset:0});
                                }
                                $comment.find('.j-comment-cancel').trigger('click');
                            }
                        } else {
                            f.alertError(errors);
                        }
                    });
                }, {noEnterSubmit: true});
            }
        });

        $block.on('click', '.j-comment-cancel', function(){
            var $comment = $(this).closest('.j-comment');
            $comment.find('.j-comment-actions').removeClass('hide');
            $comment.find('.j-comment-add-form').parent().addClass('hide');
            return false;
        });

        $block.on('click', '.j-comment-delete', function(){
            var $el = $(this);
            bff.ajax(bff.ajaxURL('bbs&ev=comments', 'delete'), {id:$el.data('id'), item_id:o.item_id, hash:app.csrf_token}, function(resp, errors) {
                if (resp && resp.success) {
                    var $bl = $el.closest('.j-comment-block');
                    $bl.after(resp.html);
                    $bl.remove();
                } else {
                    app.alert.error(errors);
                }
            });
            return false;
        });

        $block.on('click', '.j-comments-more', function(e){
            e.preventDefault();
            if($(this).data('answers')) {
                $(this).closest('.j-comment-block').find('.j-comment-block-answer').removeClass('hide');
            } else {
                $block.find('.j-comment-block:not(.j-comment-block-answer)').removeClass('hide');
            }
            $(this).closest('.j-comments-more-block').remove();
        });
    }

    return {
        init: function(options)
        {
            if(inited) return; inited = true;
            o = bff.filter('bbs.comments.settings', $.extend(o, options || {}));
            $(function(){
                init();
                bff.hook('bbs.comments.init', $block, o);
            });
        }
    };
}());

