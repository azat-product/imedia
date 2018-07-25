var bffComments = (function()
{
    var o = {}, $block = false;
    var aCommentNew = [], iCurrentShowFormComment = 0, iCommentIdLastView = null, countNewComment = 0;
    var idCommentLast = 0;
    
    function init(opts)
    {
        o = $.extend({lastID:0, url:'', block: false}, opts || {});
        $block = $(o.block);
        setIdCommentLast( o.lastID );
    }
    
    function injectComment(idCommentParent, idComment, sHtml) 
    {
        var newComment = $(document.createElement('div'));
        newComment.addClass( (idCommentParent>0 ? 'b-answer' : 'b-comment') ).attr('id','comment_id_'+idComment).html(sHtml);        
        if (idCommentParent) {
            var divChildren = $('#comment-children-'+idCommentParent, $block);
            divChildren.append( newComment );
        } else {
            newComment.insertBefore( $('#comment-children-0', $block) );
        }    
    }    
    
    function responseNewComment(selfIdComment, bNotFlushNew)
    {
        if (!bNotFlushNew) {
            $('.comment', $block).each(function(i,item){
                var divContent = $($(item).children('div.content')[0]);
                if (divContent) divContent.removeClass('new view'); 
            });
        }                   
        
        setTimeout(function(){ bff.ajax(o.url+'?act=comment-response', {'comment_id_last': idCommentLast }, function(data) 
        {
            if(!data) return; 
            var aCmt = data.aComments;
            if (aCmt.length>0 && data.nMaxIdComment) {
                setIdCommentLast( data.nMaxIdComment );
            }            
            var iCountOld=0;
            if (bNotFlushNew) {                                                        
                iCountOld = countNewComment;                        
            } else {
                aCommentNew = [];
            }
            if (selfIdComment) {
                formShow( 0 ); 
                formEnable();
            }
                                
            $.each(aCmt, function(i,comm) {   
                if (!(selfIdComment && selfIdComment == comm.id)) {
                    aCommentNew.push(comm.id);
                }                                         
                injectComment(comm.pid, comm.id, comm.html);
            });
            
            $('#comment-no', $block).slideUp();
        }); }, 1000 );
    }
    
    function setIdCommentLast(id) {
        idCommentLast = id;
    }
    
    function submit(formObj) 
    {
        bff.ajax(o.url + '?act=comment-add', $(formObj).serializeArray(), function(data) {
            if(data) {
                $('#form_comment_text', $block).prop('disabled', true);
                responseNewComment( data.comment_id, true );
            }
        }, function(showProgress) { });
    }
    
    function formEnable() {
        $('#form_comment_text', $block).prop('disabled', false);
    }

    function formShow(idComment, userName) {
        if (!$('#reply_' + iCurrentShowFormComment, $block) || !$('#reply_' + idComment, $block)) {
            return;
        } 
        var divCurrentForm = $('#reply_' + iCurrentShowFormComment, $block);
        var divNextForm    = $('#reply_' + idComment, $block);
        
        if(!idComment) $('.cancel', $block).hide();
        else $('.cancel', $block).show();
        
        if (iCurrentShowFormComment == idComment) {
            divCurrentForm.show();
            $('#form_comment_text', $block).focus().attr('value', (userName ? userName+', ': '') );
            return false;
        }
        
        divCurrentForm.hide();
        divNextForm.html( divCurrentForm.html() );
        divCurrentForm.html('');        
        divNextForm.css('display','block').show();
                
        $('#form_comment_text', $block).focus().attr('value', (userName ? userName+', ': '') );
        $('#form_comment_reply', $block).attr('value', idComment);
        iCurrentShowFormComment = idComment;
        
        return false;
    }
    
    function formHide(idComment) {
        var comm = $('#reply_'+idComment, $block);
        if(comm) {
            formEnable();
            comm.hide();
        }
    }
    
    return {init: function(opts){ init(opts); return {formShow:formShow, submit:submit}; }};
}());