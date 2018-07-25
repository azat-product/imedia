/**
 * Bff.Admin.Comments.js (bff\db\Comments component)
 * @author Tamaranga | tamaranga.com
 * @version 0.32
 * @modified 5.jul.2015
 */

var bffAdmComments = function(options, elements){ this.initialize(options, elements); }
bffAdmComments.prototype = 
{
    o: {
        url_ajax: '',
        group_id: 0,
        img: {          
            path:  '/img/admin/',
            open:  'comment-open.gif', 
            close: 'comment-close.gif'
        },
        classes: {
            visible:  'visible',
            hidden:   'hidden',            
            open:     'open',            
            close:    'close'            
        }
    },
    e: { 
        
    },    
    
    initialize: function(options, elements){
        this.o = $.extend(this.o, options || {});                 
        this.e = $.extend(this.e, elements || {});
        this.make();        
        this.aCommentNew = [];
        this.iCurrentShowFormComment = 0;    
        this.iCommentIdLastView = null;    
        this.countNewComment = 0;
        this.hideCommentForm( this.iCurrentShowFormComment );
    },

    make: function(){
        var thisObj = this;
        var aImgFolding = $('img.folding');
        aImgFolding.each(function(i,img){
            var divComment = $($(img).parent('div').children('div.comment-children')[0]);
            if (divComment && divComment.children('div.comment')[0]) {
                thisObj.makeImg( $(img) );
            } else {
                $(img).css('display','none');
            }
        });        
    },
    
    makeImg: function(img) {
        var thisObj = this;
        img.css('cursor', 'pointer').css('display','inline').
            addClass(this.o.classes.close).
            unbind('click').click(function(){ thisObj.toggleNode(img); });
    },
    
    toggleNode: function(img) {    
        if ( img.hasClass(this.o.classes.close) ) {                
            this.collapseNode(img);
        } else {                    
            this.expandNode(img);
        }
    },
    
    expandNode: function(img) {                
        img.attr('src', this.o.img.path + this.o.img.close).
            removeClass(this.o.classes.open).
            addClass(this.o.classes.close);          
        $(img.parent('div').children('div.comment-children')[0]).
                removeClass(this.o.classes.hidden).
                addClass(this.o.classes.visible);        
    },
    
    collapseNode: function(img) {
        img.attr('src', this.o.img.path + this.o.img.open).
            removeClass(this.o.classes.close).
            addClass(this.o.classes.open);                   
        $(img.parent('div').children('div.comment-children')[0]).
            removeClass(this.o.classes.visible).
            addClass(this.o.classes.hidden);        
    },
    
    expandNodeAll: function() {
        var thisObj = this;
        $('img.'+this.o.classes.open).each(function(i,img){            
            thisObj.expandNode($(img));
        });
    },
    
    collapseNodeAll: function() {
        var thisObj = this;      
        $('img.'+this.o.classes.close).each(function(i,img){            
            thisObj.collapseNode($(img));
        });
    },
    
    injectComment: function(idCommentParent, idComment, sHtml) {        
        var newComment = $(document.createElement('div'));
        newComment.addClass('comment').attr('id','comment_id_'+idComment).html(sHtml);        
        if (idCommentParent) {
            this.expandNodeAll();    
            var divChildren = $('#comment-children-'+idCommentParent);        
            this.makeImg( $(divChildren.parent('div').children('img.folding')[0]) );
            divChildren.append(newComment);
        } else {
            newComment.insertBefore( $('#comment-children-0') );
        }    
    },    
    
    responseNewComment: function(objImg, selfIdComment, bNotFlushNew) 
    {
        var self = this;        
        if (!bNotFlushNew) {
            $('.comment').each(function(i,item){
                var divContent = $($(item).children('div.content')[0]);
                if (divContent) divContent.removeClass('new view'); 
            });
        }                   
        objImg = $(objImg);
        objImg.attr('src', self.o.img.path + 'comment-update-act.gif');   
        setTimeout(function(){ bff.ajax(self.o.url_ajax+'comment-response', {comment_id_last: self.idCommentLast, group_id: self.o.group_id}, function(data)
        {
                objImg.attr('src', self.o.img.path + 'comment-update.gif'); 
                if (data) 
                { 
                    var aCmt = data.aComments;                     
                    if (aCmt.length>0 && data.nMaxIdComment) {
                        self.setIdCommentLast( data.nMaxIdComment );
                        self.e.total.text( intval(self.e.total.text()) + aCmt.length );
                    }            
                    var iCountOld=0;
                    if (bNotFlushNew) {                                                        
                        iCountOld = self.countNewComment;                        
                    } else {
                        self.aCommentNew = [];
                    }
                    if (selfIdComment) {
                        self.setCountNewComment( aCmt.length-1 + iCountOld );      
                        self.hideCommentForm( self.iCurrentShowFormComment ); 
                    } else {
                        self.setCountNewComment(aCmt.length + iCountOld);
                    }                    
                    $.each(aCmt, function(i,comm) {   
                        if (!(selfIdComment && selfIdComment == comm.id)) {
                            self.aCommentNew.push(comm.id);
                        }                                         
                        self.injectComment(comm.pid, comm.id, comm.html);
                    });
                    
                    $('#comment-no').slideUp();
                }                           
            }
       ); }, 1000 );
    },
    
    setIdCommentLast: function(id) {
        this.idCommentLast = id;
    },
    
    setCountNewComment: function(count) {
        this.countNewComment = count;        
        var divCountNew = $('#new-comments');
        if(this.countNewComment>0) {
            divCountNew.text(this.countNewComment).css('display','block');            
        }else{
            this.countNewComment = 0;
            divCountNew.text(0).css('display','none');
        }
    },
    
    goNextComment: function() {        
        if(this.aCommentNew[0]) {
            this.scrollToComment(this.aCommentNew[0]);
            this.aCommentNew.splice(0,1);
        }        
        this.setCountNewComment(this.countNewComment-1);
    },
    
    scrollToComment: function(idComment) {
        var cmt=$('#comment_content_id_'+idComment);
        $.scrollTo(cmt, 500);
        if (this.iCommentIdLastView) {
            $('#comment_content_id_'+this.iCommentIdLastView).removeClass('view');
        }                
        cmt.addClass('view');
        this.iCommentIdLastView = idComment;
    },
    
    addComment: function(formObj) {              
        var self = this;
        bff.ajax(self.o.url_ajax + 'comment-add', $(formObj).serializeArray(),function(data) {
            if(data) {
                $('#form_comment_text').prop('disabled', true);
                self.responseNewComment($('#update-comments'), data.comment_id, true);
            }
        }, function(show) {
            if(show) $('#form_comment_text').addClass('loader');
            else $('#form_comment_text').removeClass('loader');
        });
    },

    moderateComment: function(link, commentId) {
        bff.ajax(this.o.url_ajax + 'comment-moderate', {comment_id: commentId, group_id: this.o.group_id}, function(data) {
            if(data) { $(link).hide(); }
        });
    },
    
    enableFormComment: function() {
        $('#form_comment_text').removeClass('loader').prop('disabled', false);
    },
    
    addCommentScroll: function(commentId) {
        this.aCommentNew.push(commentId);
        this.setCountNewComment(this.countNewComment+1);
    },
    
    deleteComment: function(link, commentId) {
        var divContent = $('#comment_content_id_'+commentId);
        if ( ! divContent || ! divContent.length) {
            return false;
        }
        
        if( ! bff.confirm('sure'))
            return false;
        
        link = $(link);
        bff.ajax(this.o.url_ajax + 'comment-delete', {comment_id: commentId, group_id: this.o.group_id}, function (data)
        {
            if(data) {                  
                divContent.addClass('del'); 
                var mod = link.parent().siblings('.mod');
                link.replaceWith('<span class="desc">Удален модератором</span>');
                if(mod.length) mod.remove();
            }
        });
    },
    
    toggleCommentForm: function(idComment, show) {
        if (!$('#reply_'+this.iCurrentShowFormComment) || !$('#reply_'+idComment)) {
            return;
        } 
        var divCurrentForm = $('#reply_'+this.iCurrentShowFormComment);
        var divNextForm    = $('#reply_'+idComment);
        
        $('#comment_preview_'+this.iCurrentShowFormComment).html('').css('display','none');
        if (this.iCurrentShowFormComment == idComment) {
            if(show === true) divCurrentForm.show();
            else if(show === false) divCurrentForm.hide();
            else divCurrentForm.toggle();
            $('#form_comment_text').focus();
            return;
        }
        
        divCurrentForm.hide();
        divNextForm.html(divCurrentForm.html());
        divCurrentForm.html('');        
        divNextForm.css('display','block').show();
                
        $('#form_comment_text').focus().attr('value', '');
        $('#form_comment_reply').attr('value', idComment);
        this.iCurrentShowFormComment = idComment;
    },
    
    hideCommentForm: function(idComment) {
        var comm = $('#reply_'+idComment);
        if(comm) {
            this.enableFormComment();
            $('#comment_preview_'+this.iCurrentShowFormComment).html('').css('display','none');
            comm.hide();
        }
    }
};