<?php
namespace App\Controller;
use App;
use Swoole;

class Myblog extends App\UserBase
{
    function write()
    {
        $_m = model('UserLogs');
        $_l = model('UserLogCat');
        if($_POST)
        {
            //如果没得到id，说明提交的是添加操作
            if(empty($_POST['title']))
            {
                return Swoole\JS::js_back('标题不能为空！');
            }
            Swoole\Filter::safe($_POST['content']);
            $_POST['content'] = Swoole\Filter::remove_xss($_POST['content']);
            Swoole\Filter::addslash($_POST['content']);

            $blog['title'] = $_POST['title'];
            $blog['content'] = $_POST['content'];
            $blog['c_id'] = (int)$_POST['c_id'];
            if(isset($_POST['act']) and $_POST['act']=='draft') $blog['dir'] = 1;
            else $blog['dir'] = 0;

            if(!empty($_POST['id']))
            {
                //如果得到id，说明提交的是修改的操作
                $id = (int)$_POST['id'];
                $det = $_m->get($id)->get();
                if($det['uid']!=$this->uid) exit('access deny!not your blog!');
                $_m->set($id,$blog);
                if(isset($_POST['autosave'])) return 1;
                else return Swoole\JS::js_back('修改成功',-2);
            }
            else
            {
                $blog['uid'] = $this->uid;
                $bid = $_m->put($blog);
                $_l->set($blog['c_id'],array('num'=>'`num`+1'));
                if(isset($_POST['autosave'])) return $bid;
                else return Swoole\JS::js_back('添加成功');
            }
        }
        else
        {
            require_once WEBPATH.'/swoole_plugin/fckeditor/Swoole.plugin.php';
            $cat = $_l->getMap(array('uid'=>$this->uid),'name');
            if(empty($cat)) $cat = array();

            if(!empty($_GET['id']))
            {
                $id = $_GET['id'];
                $det = $_m->get($id)->get();
                Swoole\Filter::deslash($det['content']);
                $form = Swoole\Form::select('c_id',$cat,$det['c_id']);
                $this->swoole->tpl->assign('det',$det);
                $editor = editor("content",$det['content'],480,false,false,array('empty'=>'请选择日志分类'));
            }
            else
            {
                $form = Swoole\Form::select('c_id',$cat,'',false,array('empty'=>'请选择日志分类'));
                $editor = editor("content",'',480,false);
            }
            $this->swoole->tpl->assign('form',$form);
            $this->swoole->tpl->assign('editor',$editor);
            $this->swoole->tpl->display();
        }
    }
    function category()
    {
        $_l= model('UserLogCat');
        if(isset($_GET['del']))
        {
            $del = $_l->get((int)$_GET['del']);
            if($del->uid!=$this->uid) die('Access deny');
            $del->delete();
            return Swoole\JS::js_back('删除成功！');
        }
        if(!empty($_POST['name']))
        {
            $_POST['name'] = trim($_POST['name']);
            $data['uid'] = $this->uid;
            $data['name'] = $_POST['name'];
            if($_l->exists($data))
            {
                if($_POST['ajax']) echo 'exists';
                else return Swoole\JS::js_back('添加失败，已存在！');
            }
            else
            {
               $id = $_l->put($data);
                if($_POST['ajax']) echo $id;
                else return Swoole\JS::js_back('添加成功！');
            }
        }
        $gets['uid'] = $this->uid;
        $gets['page'] = empty($_GET['page'])?1:(int)$_GET['page'];
        $gets['pagesize'] =15;
        $pager = '';
        $list = $_l->gets($gets,$pager);
        $this->swoole->tpl->assign('list',$list);
        $pager = array('total'=>$pager->total,'render'=>$pager->render());
        $this->swoole->tpl->assign('pager',$pager);
        $this->swoole->tpl->display();
    }

    function index()
    {
        $_m = model('UserLogs');
        if(isset($_GET['del']))
        {
            $_m->del((int)$_GET['del']);
            return Swoole\JS::js_back('删除成功！');
        }
        if(isset($_GET['act']) and $_GET['act']=='draft') $gets['dir'] = 1;
        else $gets['dir'] = 0;

        $gets['uid'] = $this->uid;
        $gets['page'] = empty($_GET['page'])?1:(int)$_GET['page'];
        $gets['pagesize'] =15;
        $pager = '';
        $list = $_m->gets($gets,$pager);
        $this->swoole->tpl->assign('list',$list);
        $pager = array('total'=>$pager->total,'render'=>$pager->render());
        $this->swoole->tpl->assign('pager',$pager);
        $this->swoole->tpl->display();
    }
}